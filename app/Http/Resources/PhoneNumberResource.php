<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneNumberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phoneable_id' => $this->phoneable_id,
            'phoneable_type' => $this->phoneable_type,
            'phone_number' => $this->phone_number,
            'formatted_number' => $this->formatted_number,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'label' => $this->label,
            'display_label' => $this->display_label,
            'is_primary' => $this->is_primary,
            'is_favorite' => $this->is_favorite,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Zusätzliche berechnete Felder
            'digits_only' => $this->when(true, function () {
                return preg_replace('/[^\d]/', '', $this->phone_number);
            }),
            
            // Beziehungsdaten nur wenn geladen
            'owner' => $this->whenLoaded('phoneable', function () {
                // Hier könnte je nach Typ eine spezifische Resource verwendet werden
                return [
                    'id' => $this->phoneable->id ?? null,
                    'type' => $this->phoneable_type,
                    // Weitere Felder könnten hier basierend auf dem Typ hinzugefügt werden
                ];
            }),

            // Metadaten
            'meta' => [
                'is_german_number' => $this->when(true, function () {
                    return str_starts_with($this->phone_number, '+49') || 
                           str_starts_with($this->phone_number, '0');
                }),
                'is_mobile' => $this->when(true, function () {
                    $digitsOnly = preg_replace('/[^\d]/', '', $this->phone_number);
                    // Deutsche Mobilnummern beginnen mit 01 (nach Ländercode)
                    if (str_starts_with($this->phone_number, '+49')) {
                        $digitsOnly = substr($digitsOnly, 2); // +49 entfernen
                        return str_starts_with($digitsOnly, '1');
                    } elseif (str_starts_with($this->phone_number, '0')) {
                        return str_starts_with($digitsOnly, '01');
                    }
                    return false;
                }),
                'character_count' => strlen($this->phone_number),
                'digit_count' => strlen(preg_replace('/[^\d]/', '', $this->phone_number)),
            ]
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => route('api.phone-numbers.show', $this->id),
                'update' => route('api.phone-numbers.update', $this->id),
                'delete' => route('api.phone-numbers.destroy', $this->id),
                'make_primary' => route('api.phone-numbers.make-primary', $this->id),
            ],
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function withResponse(Request $request, $response): void
    {
        // Hier können Response-Headers oder andere Anpassungen vorgenommen werden
        $response->header('X-Resource-Type', 'PhoneNumber');
        $response->header('X-Resource-Version', '1.0');
    }
}
