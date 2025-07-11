<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DummyFieldConfig extends Model
{
    protected $fillable = [
        'entity_type',
        'field_key',
        'field_label',
        'field_description',
        'is_active',
        'sort_order',
        'column_span',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope für aktive Felder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für sortierte Felder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope für bestimmten Entity Type
     */
    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Gibt die verfügbaren Entity Types zurück
     */
    public static function getEntityTypes(): array
    {
        return [
            'supplier_contract' => 'Lieferantenverträge',
            'customer' => 'Kunden',
            'supplier' => 'Lieferanten',
            'solar_plant' => 'Solaranlagen',
        ];
    }

    /**
     * Gibt die verfügbaren Field Keys zurück
     */
    public static function getAvailableFieldKeys(): array
    {
        return [
            'custom_field_1' => 'Zusatzfeld 1',
            'custom_field_2' => 'Zusatzfeld 2',
            'custom_field_3' => 'Zusatzfeld 3',
            'custom_field_4' => 'Zusatzfeld 4',
            'custom_field_5' => 'Zusatzfeld 5',
        ];
    }

    /**
     * Gibt die aktiven Felder für einen bestimmten Entity Type zurück
     */
    public static function getActiveFields(string $entityType = 'supplier_contract'): array
    {
        return static::forEntity($entityType)->active()->ordered()->get()->toArray();
    }

    /**
     * Generiert Schema-Felder für eine bestimmte Entität
     */
    public static function getDummyFieldsSchema(string $entityType): array
    {
        $fields = [];
        
        try {
            $dummyFields = static::forEntity($entityType)
                ->active()
                ->ordered()
                ->get();

            foreach ($dummyFields as $dummyField) {
                $field = \Filament\Forms\Components\TextInput::make($dummyField->field_key)
                    ->label($dummyField->field_label)
                    ->maxLength(1000);

                if ($dummyField->field_description) {
                    $field = $field->helperText($dummyField->field_description);
                }

                // Spaltenbreite konfigurieren
                if ($dummyField->column_span == 2) {
                    $field = $field->columnSpanFull();
                }
                // column_span == 1 ist Standard (halbe Breite), keine weitere Konfiguration nötig

                $fields[] = $field;
            }
        } catch (\Exception $e) {
            // Fallback falls DummyFieldConfig Tabelle noch nicht existiert
            return [];
        }

        return $fields;
    }
}
