<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components;

class FieldConfig extends Model
{
    protected $fillable = [
        'entity_type',
        'field_key',
        'field_label',
        'field_description',
        'field_type',
        'field_options',
        'section_name',
        'section_sort_order',
        'sort_order',
        'column_span',
        'is_required',
        'is_active',
        'is_system_field',
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_system_field' => 'boolean',
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
        return $query->orderBy('section_sort_order')
                    ->orderBy('sort_order');
    }

    /**
     * Scope für bestimmten Entity Type
     */
    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope für System-Felder
     */
    public function scopeSystemFields($query)
    {
        return $query->where('is_system_field', true);
    }

    /**
     * Scope für Custom-Felder
     */
    public function scopeCustomFields($query)
    {
        return $query->where('is_system_field', false);
    }

    /**
     * Scope für bestimmte Section
     */
    public function scopeForSection($query, string $sectionName)
    {
        return $query->where('section_name', $sectionName);
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
     * Gibt die verfügbaren Field Types zurück
     */
    public static function getFieldTypes(): array
    {
        return [
            'text' => 'Text',
            'textarea' => 'Mehrzeiliger Text',
            'select' => 'Auswahl',
            'date' => 'Datum',
            'number' => 'Zahl',
            'toggle' => 'Ein/Aus',
            'email' => 'E-Mail',
            'url' => 'URL',
            'password' => 'Passwort',
        ];
    }

    /**
     * Gibt die verfügbaren Sections für einen Entity Type zurück
     */
    public static function getSectionsForEntity(string $entityType): array
    {
        $sections = [
            'supplier_contract' => [
                'Vertragsdaten' => 1,
                'Laufzeit & Wert' => 2,
                'Vertragserkennung' => 3,
                'Zusätzliche Informationen' => 4,
            ],
            'customer' => [
                'Grunddaten' => 1,
                'Kontaktdaten' => 2,
                'Zusätzliche Informationen' => 3,
            ],
            'supplier' => [
                'Grunddaten' => 1,
                'Kontaktdaten' => 2,
                'Zusätzliche Informationen' => 3,
            ],
            'solar_plant' => [
                'Anlagendaten' => 1,
                'Technische Daten' => 2,
                'Zusätzliche Informationen' => 3,
            ],
        ];

        return $sections[$entityType] ?? [];
    }

    /**
     * Generiert das komplette Form Schema für eine Entität
     */
    public static function getFormSchema(string $entityType): array
    {
        $sections = [];
        
        try {
            $fields = static::forEntity($entityType)
                ->active()
                ->ordered()
                ->get()
                ->groupBy('section_name');

            foreach ($fields as $sectionName => $sectionFields) {
                $sectionSchema = [];
                
                foreach ($sectionFields as $fieldConfig) {
                    $field = static::createFilamentField($fieldConfig);
                    if ($field) {
                        $sectionSchema[] = $field;
                    }
                }

                if (!empty($sectionSchema)) {
                    $sections[] = Components\Section::make($sectionName)
                        ->schema($sectionSchema)
                        ->columns(2);
                }
            }
        } catch (\Exception $e) {
            // Fallback falls FieldConfig Tabelle noch nicht existiert
            return [];
        }

        return $sections;
    }

    /**
     * Erstellt ein Filament Form Component basierend auf der Konfiguration
     */
    protected static function createFilamentField(FieldConfig $config): ?Components\Component
    {
        $field = null;

        switch ($config->field_type) {
            case 'text':
                $field = Components\TextInput::make($config->field_key)
                    ->label($config->field_label);
                
                if ($config->field_options['max_length'] ?? null) {
                    $field = $field->maxLength($config->field_options['max_length']);
                }
                
                if ($config->field_options['placeholder'] ?? null) {
                    $field = $field->placeholder($config->field_options['placeholder']);
                }
                break;

            case 'textarea':
                $field = Components\Textarea::make($config->field_key)
                    ->label($config->field_label);
                
                if ($config->field_options['rows'] ?? null) {
                    $field = $field->rows($config->field_options['rows']);
                }
                
                if ($config->field_options['max_length'] ?? null) {
                    $field = $field->maxLength($config->field_options['max_length']);
                }
                break;

            case 'select':
                $field = Components\Select::make($config->field_key)
                    ->label($config->field_label);
                
                if ($config->field_options['options'] ?? null) {
                    $field = $field->options($config->field_options['options']);
                }
                
                if ($config->field_options['searchable'] ?? false) {
                    $field = $field->searchable();
                }
                
                if ($config->field_options['preload'] ?? false) {
                    $field = $field->preload();
                }
                
                if ($config->field_options['default'] ?? null) {
                    $field = $field->default($config->field_options['default']);
                }
                break;

            case 'date':
                $field = Components\DatePicker::make($config->field_key)
                    ->label($config->field_label);
                break;

            case 'number':
                $field = Components\TextInput::make($config->field_key)
                    ->label($config->field_label)
                    ->numeric();
                
                if ($config->field_options['step'] ?? null) {
                    $field = $field->step($config->field_options['step']);
                }
                
                if ($config->field_options['prefix'] ?? null) {
                    $field = $field->prefix($config->field_options['prefix']);
                }
                
                if ($config->field_options['suffix'] ?? null) {
                    $field = $field->suffix($config->field_options['suffix']);
                }
                break;

            case 'toggle':
                $field = Components\Toggle::make($config->field_key)
                    ->label($config->field_label);
                
                if ($config->field_options['default'] ?? null) {
                    $field = $field->default($config->field_options['default']);
                }
                break;

            case 'email':
                $field = Components\TextInput::make($config->field_key)
                    ->label($config->field_label)
                    ->email();
                break;

            case 'url':
                $field = Components\TextInput::make($config->field_key)
                    ->label($config->field_label)
                    ->url();
                break;

            case 'password':
                $field = Components\TextInput::make($config->field_key)
                    ->label($config->field_label)
                    ->password();
                break;
        }

        if ($field) {
            // Gemeinsame Konfigurationen anwenden
            if ($config->field_description) {
                $field = $field->helperText($config->field_description);
            }

            if ($config->is_required) {
                $field = $field->required();
            }

            // Spaltenbreite konfigurieren
            if ($config->column_span == 2) {
                $field = $field->columnSpanFull();
            }

            // Unique Validierung für bestimmte Felder
            if ($config->field_options['unique'] ?? false) {
                $field = $field->unique(ignoreRecord: true);
            }
        }

        return $field;
    }

    /**
     * Gibt die aktiven Felder für einen bestimmten Entity Type zurück
     */
    public static function getActiveFields(string $entityType): array
    {
        return static::forEntity($entityType)->active()->ordered()->get()->toArray();
    }

    /**
     * Migriert bestehende DummyFieldConfig Daten
     */
    public static function migrateDummyFieldConfigs(): void
    {
        if (!class_exists(DummyFieldConfig::class)) {
            return;
        }

        $dummyConfigs = DummyFieldConfig::all();
        
        foreach ($dummyConfigs as $dummyConfig) {
            static::updateOrCreate([
                'entity_type' => $dummyConfig->entity_type,
                'field_key' => $dummyConfig->field_key,
            ], [
                'field_label' => $dummyConfig->field_label,
                'field_description' => $dummyConfig->field_description,
                'field_type' => 'text', // Dummy-Felder sind alle Text-Felder
                'field_options' => ['max_length' => 1000],
                'section_name' => 'Zusätzliche Informationen',
                'section_sort_order' => 99, // Am Ende
                'sort_order' => $dummyConfig->sort_order ?? 0,
                'column_span' => $dummyConfig->column_span ?? 1,
                'is_required' => false,
                'is_active' => $dummyConfig->is_active ?? true,
                'is_system_field' => false, // Dummy-Felder sind Custom-Felder
            ]);
        }
    }
}