<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AppToken extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'token',
        'abilities',
        'expires_at',
        'is_active',
        'last_used_at',
        'created_by_ip',
        'app_type',
        'app_version',
        'device_info',
        'notes',
        'allowed_customers',
        'allowed_suppliers',
        'allowed_solar_plants',
        'allowed_projects',
        'restrict_customers',
        'restrict_suppliers',
        'restrict_solar_plants',
        'restrict_projects',
    ];

    protected $casts = [
        'abilities' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'allowed_customers' => 'array',
        'allowed_suppliers' => 'array',
        'allowed_solar_plants' => 'array',
        'allowed_projects' => 'array',
        'restrict_customers' => 'boolean',
        'restrict_suppliers' => 'boolean',
        'restrict_solar_plants' => 'boolean',
        'restrict_projects' => 'boolean',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Beziehung zum User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verfügbare App-Typen
     */
    public static function getAppTypes(): array
    {
        return [
            'mobile_app' => 'Mobile App',
            'desktop_app' => 'Desktop App',
            'web_app' => 'Web App',
            'third_party' => 'Third Party',
            'integration' => 'Integration',
        ];
    }

    /**
     * Verfügbare Token-Berechtigungen
     */
    public static function getAvailableAbilities(): array
    {
        return [
            // Aufgaben-Verwaltung
            'tasks:read' => 'Aufgaben lesen',
            'tasks:create' => 'Aufgaben erstellen',
            'tasks:update' => 'Aufgaben bearbeiten',
            'tasks:delete' => 'Aufgaben löschen',
            'tasks:assign' => 'Aufgaben zuweisen',
            'tasks:status' => 'Status ändern',
            'tasks:notes' => 'Notizen verwalten',
            'tasks:documents' => 'Dokumente verwalten',
            'tasks:time' => 'Zeiten erfassen',
            
            // Solaranlagen-Verwaltung
            'solar-plants:read' => 'Solaranlagen lesen',
            'solar-plants:create' => 'Solaranlagen erstellen',
            'solar-plants:update' => 'Solaranlagen bearbeiten',
            'solar-plants:delete' => 'Solaranlagen löschen',
            
            // Kunden-Verwaltung
            'customers:read' => 'Kunden lesen',
            'customers:create' => 'Kunden erstellen',
            'customers:update' => 'Kunden bearbeiten',
            'customers:delete' => 'Kunden löschen',
            'customers:status' => 'Kunden-Status ändern',
            
            // Lieferanten-Verwaltung
            'suppliers:read' => 'Lieferanten lesen',
            'suppliers:create' => 'Lieferanten erstellen',
            'suppliers:update' => 'Lieferanten bearbeiten',
            'suppliers:delete' => 'Lieferanten löschen',
            'suppliers:status' => 'Lieferanten-Status ändern',
            
            // Projekt-Verwaltung
            'projects:read' => 'Projekte lesen',
            'projects:create' => 'Projekte erstellen',
            'projects:update' => 'Projekte bearbeiten',
            'projects:delete' => 'Projekte löschen',
            'projects:status' => 'Projekt-Status ändern',
            
            // Meilensteine
            'milestones:read' => 'Meilensteine lesen',
            'milestones:create' => 'Meilensteine erstellen',
            'milestones:update' => 'Meilensteine bearbeiten',
            'milestones:delete' => 'Meilensteine löschen',
            'milestones:status' => 'Meilenstein-Status ändern',
            
            // Termine
            'appointments:read' => 'Termine lesen',
            'appointments:create' => 'Termine erstellen',
            'appointments:update' => 'Termine bearbeiten',
            'appointments:delete' => 'Termine löschen',
            'appointments:status' => 'Termin-Status ändern',
            
            // Kosten-Management
            'costs:read' => 'Kosten lesen',
            'costs:create' => 'Kosten erstellen',
            'costs:reports' => 'Kostenberichte',
            
            // Benutzer & Profil
            'user:profile' => 'Profil lesen',
            
            // Benachrichtigungen
            'notifications:read' => 'Benachrichtigungen lesen',
            'notifications:create' => 'Benachrichtigungen erstellen',
            
            // Telefonnummern-Management
            'phone-numbers:read' => 'Telefonnummern lesen',
            'phone-numbers:create' => 'Telefonnummern erstellen',
            'phone-numbers:update' => 'Telefonnummern bearbeiten',
            'phone-numbers:delete' => 'Telefonnummern löschen',
        ];
    }

    /**
     * Generiere einen neuen Token
     */
    public static function generateToken(): string
    {
        return 'sb_' . Str::random(64);
    }

    /**
     * Erstelle einen neuen App-Token
     */
    public static function createToken(
        int $userId,
        string $name,
        array $abilities = [],
        string $appType = 'mobile_app',
        ?string $appVersion = null,
        ?string $deviceInfo = null,
        ?string $notes = null
    ): self {
        $token = self::generateToken();
        
        return self::create([
            'user_id' => $userId,
            'name' => $name,
            'token' => hash('sha256', $token),
            'abilities' => $abilities ?: ['tasks:read'],
            'expires_at' => now()->addYears(2),
            'is_active' => true,
            'created_by_ip' => request()->ip(),
            'app_type' => $appType,
            'app_version' => $appVersion,
            'device_info' => $deviceInfo,
            'notes' => $notes,
        ]);
    }

    /**
     * Prüfe ob Token gültig ist
     */
    public function isValid(): bool
    {
        return $this->is_active && 
               $this->expires_at > now() &&
               $this->user && 
               $this->user->is_active;
    }

    /**
     * Prüfe ob Token eine bestimmte Berechtigung hat
     */
    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? []);
    }

    /**
     * Markiere Token als verwendet
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Erneuere Token (verlängere Gültigkeit)
     */
    public function renew(): void
    {
        $this->update(['expires_at' => now()->addYears(2)]);
    }

    /**
     * Deaktiviere Token
     */
    public function disable(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Aktiviere Token
     */
    public function enable(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Scope für aktive Tokens
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für nicht abgelaufene Tokens
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope für gültige Tokens
     */
    public function scopeValid($query)
    {
        return $query->active()->notExpired();
    }

    /**
     * Scope für bald ablaufende Tokens (innerhalb von 30 Tagen)
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('expires_at', '>', now())
                    ->where('expires_at', '<', now()->addDays(30));
    }

    /**
     * Finde Token durch hash
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('token', hash('sha256', $token))->first();
    }

    /**
     * Get App Type Label
     */
    public function getAppTypeLabelAttribute(): string
    {
        return self::getAppTypes()[$this->app_type] ?? ($this->app_type ?? 'Unbekannt');
    }

    /**
     * Get Abilities Labels
     */
    public function getAbilitiesLabelsAttribute(): array
    {
        $availableAbilities = self::getAvailableAbilities();
        return array_map(function ($ability) use ($availableAbilities) {
            return $availableAbilities[$ability] ?? $ability;
        }, $this->abilities ?? []);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) {
            return 'Deaktiviert';
        }
        
        if ($this->expires_at < now()) {
            return 'Abgelaufen';
        }
        
        if ($this->expires_at < now()->addDays(30)) {
            return 'Läuft bald ab';
        }
        
        return 'Aktiv';
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        if (!$this->is_active) {
            return 'gray';
        }
        
        if ($this->expires_at < now()) {
            return 'danger';
        }
        
        if ($this->expires_at < now()->addDays(30)) {
            return 'warning';
        }
        
        return 'success';
    }

    // Ressourcen-Beschränkungs-Methoden

    /**
     * Prüfe ob Token Zugriff auf bestimmten Kunden hat
     */
    public function canAccessCustomer(int $customerId): bool
    {
        if (!$this->restrict_customers) {
            return true; // Keine Beschränkung
        }
        
        return in_array($customerId, $this->allowed_customers ?? []);
    }

    /**
     * Prüfe ob Token Zugriff auf bestimmten Lieferanten hat
     */
    public function canAccessSupplier(int $supplierId): bool
    {
        if (!$this->restrict_suppliers) {
            return true; // Keine Beschränkung
        }
        
        return in_array($supplierId, $this->allowed_suppliers ?? []);
    }

    /**
     * Prüfe ob Token Zugriff auf bestimmte Solaranlage hat
     */
    public function canAccessSolarPlant(int $solarPlantId): bool
    {
        if (!$this->restrict_solar_plants) {
            return true; // Keine Beschränkung
        }
        
        return in_array($solarPlantId, $this->allowed_solar_plants ?? []);
    }

    /**
     * Prüfe ob Token Zugriff auf bestimmtes Projekt hat
     */
    public function canAccessProject(int $projectId): bool
    {
        if (!$this->restrict_projects) {
            return true; // Keine Beschränkung
        }
        
        return in_array($projectId, $this->allowed_projects ?? []);
    }

    /**
     * Setze erlaubte Kunden
     */
    public function setAllowedCustomers(array $customerIds): void
    {
        $this->update([
            'allowed_customers' => $customerIds,
            'restrict_customers' => !empty($customerIds)
        ]);
    }

    /**
     * Setze erlaubte Lieferanten
     */
    public function setAllowedSuppliers(array $supplierIds): void
    {
        $this->update([
            'allowed_suppliers' => $supplierIds,
            'restrict_suppliers' => !empty($supplierIds)
        ]);
    }

    /**
     * Setze erlaubte Solaranlagen
     */
    public function setAllowedSolarPlants(array $solarPlantIds): void
    {
        $this->update([
            'allowed_solar_plants' => $solarPlantIds,
            'restrict_solar_plants' => !empty($solarPlantIds)
        ]);
    }

    /**
     * Setze erlaubte Projekte
     */
    public function setAllowedProjects(array $projectIds): void
    {
        $this->update([
            'allowed_projects' => $projectIds,
            'restrict_projects' => !empty($projectIds)
        ]);
    }

    /**
     * Prüfe ob Token Zugriff auf eine Aufgabe hat (basierend auf deren Ressourcen)
     */
    public function canAccessTask($task): bool
    {
        // Kunde prüfen
        if ($task->customer_id && !$this->canAccessCustomer($task->customer_id)) {
            return false;
        }

        // Lieferant prüfen
        if ($task->supplier_id && !$this->canAccessSupplier($task->supplier_id)) {
            return false;
        }

        // Solaranlage prüfen
        if ($task->solar_plant_id && !$this->canAccessSolarPlant($task->solar_plant_id)) {
            return false;
        }

        // Projekt prüfen (falls vorhanden)
        if (isset($task->project_id) && $task->project_id && !$this->canAccessProject($task->project_id)) {
            return false;
        }

        return true;
    }

    /**
     * Hole erlaubte Ressourcen-IDs für Query-Filtering
     */
    public function getAllowedResourceIds(string $resourceType): ?array
    {
        switch ($resourceType) {
            case 'customers':
                return $this->restrict_customers ? $this->allowed_customers : null;
            case 'suppliers':
                return $this->restrict_suppliers ? $this->allowed_suppliers : null;
            case 'solar_plants':
                return $this->restrict_solar_plants ? $this->allowed_solar_plants : null;
            case 'projects':
                return $this->restrict_projects ? $this->allowed_projects : null;
            default:
                return null;
        }
    }
}
