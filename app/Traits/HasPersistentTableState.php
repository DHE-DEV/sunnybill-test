<?php

namespace App\Traits;

use App\Models\UserTablePreference;
use Illuminate\Support\Facades\Auth;

trait HasPersistentTableState
{
    protected function getTableName(): string
    {
        // Für RelationManager
        if (property_exists($this, 'relationship')) {
            return static::$relationship . '_relation';
        }
        
        // Für Resource Pages
        if (property_exists(static::class, 'resource')) {
            return class_basename(static::$resource::getModel());
        }
        
        // Fallback: Klassenname verwenden
        return class_basename(static::class);
    }

    public function updatedTableFilters(): void
    {
        $this->saveTableState();
    }

    public function updatedTableSearch(): void
    {
        $this->saveTableState();
    }

    public function updatedTableSortColumn(): void
    {
        $this->saveTableState();
    }

    public function updatedTableSortDirection(): void
    {
        $this->saveTableState();
    }

    protected function saveTableState(): void
    {
        if (!Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $tableName = $this->getTableName();

        $data = [];

        // Speichere Filter
        if (!empty($this->tableFilters)) {
            $data['filters'] = $this->tableFilters;
        }

        // Speichere Suche
        if (!empty($this->tableSearch)) {
            $data['search'] = ['global' => $this->tableSearch];
        }

        // Speichere Sortierung
        if (!empty($this->tableSortColumn) || !empty($this->tableSortDirection)) {
            $data['sort'] = [
                'column' => $this->tableSortColumn,
                'direction' => $this->tableSortDirection,
            ];
        }

        // Speichere nur wenn Daten vorhanden sind
        if (!empty($data)) {
            UserTablePreference::savePreferences($userId, $tableName, $data);
        }
    }

    protected function loadTableState(): void
    {
        if (!Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $tableName = $this->getTableName();

        $preferences = UserTablePreference::getPreferences($userId, $tableName);

        if (!$preferences) {
            return;
        }

        // Lade Filter
        if (isset($preferences['filters'])) {
            $this->tableFilters = $preferences['filters'];
        }

        // Lade Suche
        if (isset($preferences['search']['global'])) {
            $this->tableSearch = $preferences['search']['global'];
        }

        // Lade Sortierung
        if (isset($preferences['sort'])) {
            $this->tableSortColumn = $preferences['sort']['column'] ?? null;
            $this->tableSortDirection = $preferences['sort']['direction'] ?? null;
        }
    }

    public function mount(): void
    {
        parent::mount();
        $this->loadTableState();
    }

    public function shouldPersistTableFiltersInSession(): bool
    {
        return false; // Deaktiviere Session-basierte Persistierung
    }

    public function shouldPersistTableSearchInSession(): bool
    {
        return false;
    }

    public function shouldPersistTableSortInSession(): bool
    {
        return false;
    }

    public function shouldPersistTableColumnSearchesInSession(): bool
    {
        return false;
    }
}
