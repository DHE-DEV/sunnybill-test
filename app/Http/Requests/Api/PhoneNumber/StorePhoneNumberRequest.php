<?php

namespace App\Http\Requests\Api\PhoneNumber;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePhoneNumberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Hier kann spezifische Autorisierungslogik implementiert werden
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Für User-spezifische API-Routen sind phoneable_id und phoneable_type optional
        // da sie über die Route-Parameter kommen
        $isUserSpecificRoute = $this->route('userId') !== null;
        
        return [
            'phoneable_id' => [
                $isUserSpecificRoute ? 'sometimes' : 'required',
                'string',
                'uuid',
            ],
            'phoneable_type' => [
                $isUserSpecificRoute ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'phone_number' => [
                'required',
                'string',
                'max:50',
                'regex:/^[\+]?[0-9\s\-\(\)\/]{7,20}$/', // Erlaubt verschiedene Telefonnummer-Formate
            ],
            'type' => [
                'required',
                'string',
                'in:business,private,mobile',
            ],
            'label' => [
                'nullable',
                'string',
                'max:100',
            ],
            'is_primary' => [
                'sometimes',
                'boolean',
            ],
            'is_favorite' => [
                'sometimes',
                'boolean',
            ],
            'sort_order' => [
                'sometimes',
                'integer',
                'min:0',
                'max:999999',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'phoneable_id.required' => 'Die Besitzer-ID ist erforderlich.',
            'phoneable_id.uuid' => 'Die Besitzer-ID muss eine gültige UUID sein.',
            'phoneable_type.required' => 'Der Besitzer-Typ ist erforderlich.',
            'phoneable_type.max' => 'Der Besitzer-Typ darf maximal :max Zeichen lang sein.',
            'phone_number.required' => 'Die Telefonnummer ist erforderlich.',
            'phone_number.max' => 'Die Telefonnummer darf maximal :max Zeichen lang sein.',
            'phone_number.regex' => 'Die Telefonnummer hat ein ungültiges Format. Erlaubt sind Zahlen, Leerzeichen, Bindestriche, Klammern und ein optionales Pluszeichen am Anfang.',
            'type.required' => 'Der Telefonnummer-Typ ist erforderlich.',
            'type.in' => 'Der Telefonnummer-Typ muss einer der folgenden Werte sein: business, private, mobile.',
            'label.max' => 'Das Label darf maximal :max Zeichen lang sein.',
            'is_primary.boolean' => 'Das Feld "Hauptnummer" muss true oder false sein.',
            'is_favorite.boolean' => 'Das Feld "Favorit" muss true oder false sein.',
            'sort_order.integer' => 'Die Sortierreihenfolge muss eine ganze Zahl sein.',
            'sort_order.min' => 'Die Sortierreihenfolge muss mindestens :min sein.',
            'sort_order.max' => 'Die Sortierreihenfolge darf maximal :max sein.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'phoneable_id' => 'Besitzer-ID',
            'phoneable_type' => 'Besitzer-Typ',
            'phone_number' => 'Telefonnummer',
            'type' => 'Typ',
            'label' => 'Label',
            'is_primary' => 'Hauptnummer',
            'is_favorite' => 'Favorit',
            'sort_order' => 'Sortierreihenfolge',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Setze Standardwerte falls nicht angegeben
        if (!$this->has('is_primary')) {
            $this->merge(['is_primary' => false]);
        }

        if (!$this->has('is_favorite')) {
            $this->merge(['is_favorite' => false]);
        }

        if (!$this->has('sort_order')) {
            $this->merge(['sort_order' => 0]);
        }

        // Bereinige Telefonnummer von überflüssigen Leerzeichen
        if ($this->has('phone_number')) {
            $phoneNumber = trim($this->input('phone_number'));
            // Entferne mehrfache Leerzeichen
            $phoneNumber = preg_replace('/\s+/', ' ', $phoneNumber);
            $this->merge(['phone_number' => $phoneNumber]);
        }

        // Bereinige Label
        if ($this->has('label') && !empty($this->input('label'))) {
            $label = trim($this->input('label'));
            $this->merge(['label' => $label ?: null]);
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Die übermittelten Daten sind ungültig.',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Zusätzliche Validierungslogik kann hier hinzugefügt werden
            
            // Prüfe ob der phoneable_type eine gültige Klasse ist
            if ($this->has('phoneable_type')) {
                $phoneableType = $this->input('phoneable_type');
                if (!class_exists($phoneableType)) {
                    $validator->errors()->add('phoneable_type', 'Der angegebene Besitzer-Typ existiert nicht.');
                }
            }

            // Erweiterte Telefonnummer-Validierung
            if ($this->has('phone_number')) {
                $phoneNumber = $this->input('phone_number');
                
                // Prüfe auf Mindestlänge nach Entfernung aller Sonderzeichen
                $digitsOnly = preg_replace('/[^\d]/', '', $phoneNumber);
                if (strlen($digitsOnly) < 7) {
                    $validator->errors()->add('phone_number', 'Die Telefonnummer muss mindestens 7 Ziffern enthalten.');
                }
                
                // Prüfe auf maximale Länge der Ziffern
                if (strlen($digitsOnly) > 15) {
                    $validator->errors()->add('phone_number', 'Die Telefonnummer darf maximal 15 Ziffern enthalten.');
                }
            }
        });
    }
}
