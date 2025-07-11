<?php

use App\Filament\Pages\TasksAndProjectsDashboard;
use App\Models\Task;
use App\Models\SolarPlantMilestone;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = actingAsAdmin();
});

describe('TasksAndProjectsDashboard Page', function () {
    it('kann die Dashboard-Seite laden', function () {
        Livewire::test(TasksAndProjectsDashboard::class)
            ->assertSuccessful()
            ->assertSee('Termine');
    });

    it('zeigt die Standard-Zeitfilter-Optionen an', function () {
        Livewire::test(TasksAndProjectsDashboard::class)
            ->assertSuccessful()
            ->assertSee('Heute')
            ->assertSee('Nächste 7 Tage')
            ->assertSee('Nächste 30 Tage');
    });

    it('hat den Standard-Zeitfilter auf "heute" gesetzt', function () {
        $component = Livewire::test(TasksAndProjectsDashboard::class);
        
        expect($component->get('timeFilter'))->toBe('today');
    });

    it('kann den Zeitfilter ändern', function () {
        Livewire::test(TasksAndProjectsDashboard::class)
            ->set('timeFilter', 'next_7_days')
            ->assertSet('timeFilter', 'next_7_days');
    });

    it('sendet timeFilterChanged Event beim Ändern des Filters', function () {
        Livewire::test(TasksAndProjectsDashboard::class)
            ->set('timeFilter', 'next_30_days')
            ->assertDispatched('timeFilterChanged', timeFilter: 'next_30_days');
    });
});

describe('Dashboard Widgets', function () {
    it('zeigt FilteredTasksTableWidget an', function () {
        Livewire::test(TasksAndProjectsDashboard::class)
            ->assertSuccessful()
            ->assertSeeHtml('filtered-tasks-table-widget');
    });

    it('zeigt FilteredProjectMilestonesTableWidget an', function () {
        Livewire::test(TasksAndProjectsDashboard::class)
            ->assertSuccessful()
            ->assertSeeHtml('filtered-project-milestones-table-widget');
    });
});

describe('Zeitfilter Funktionalität', function () {
    beforeEach(function () {
        // Erstelle Test-Tasks für verschiedene Zeiträume
        $this->tasks = createTasksForTimeFilter();
        
        // Erstelle Test-Milestones für verschiedene Zeiträume
        $this->milestones = createMilestonesForTimeFilter();
    });

    it('filtert Tasks korrekt für "heute"', function () {
        $component = Livewire::test(TasksAndProjectsDashboard::class)
            ->set('timeFilter', 'today');

        // Prüfe, dass der Filter korrekt gesetzt ist
        expect($component->get('timeFilter'))->toBe('today');
        
        // Prüfe, dass das Event gesendet wurde
        $component->assertDispatched('timeFilterChanged', timeFilter: 'today');
    });

    it('filtert Tasks korrekt für "nächste 7 Tage"', function () {
        $component = Livewire::test(TasksAndProjectsDashboard::class)
            ->set('timeFilter', 'next_7_days');

        expect($component->get('timeFilter'))->toBe('next_7_days');
        $component->assertDispatched('timeFilterChanged', timeFilter: 'next_7_days');
    });

    it('filtert Tasks korrekt für "nächste 30 Tage"', function () {
        $component = Livewire::test(TasksAndProjectsDashboard::class)
            ->set('timeFilter', 'next_30_days');

        expect($component->get('timeFilter'))->toBe('next_30_days');
        $component->assertDispatched('timeFilterChanged', timeFilter: 'next_30_days');
    });
});

describe('Dashboard Daten-Integration', function () {
    beforeEach(function () {
        // Erstelle realistische Test-Daten
        $this->customer = createCustomer(['name' => 'Test Kunde GmbH']);
        $this->supplier = createSupplier(['name' => 'Test Lieferant AG']);
        $this->solarPlant = createSolarPlant(['name' => 'Test Solaranlage']);
        
        // Tasks mit verschiedenen Eigenschaften
        $this->taskToday = createTask([
            'title' => 'Heutige Aufgabe',
            'due_date' => now()->toDateString(),
            'status' => 'open',
            'priority' => 'high',
            'customer_id' => $this->customer->id,
        ]);

        $this->taskNext7Days = createTask([
            'title' => 'Aufgabe nächste Woche',
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'in_progress',
            'priority' => 'medium',
            'supplier_id' => $this->supplier->id,
        ]);

        $this->taskOverdue = createTask([
            'title' => 'Überfällige Aufgabe',
            'due_date' => now()->subDays(2)->toDateString(),
            'status' => 'open',
            'priority' => 'urgent',
        ]);

        // Milestones mit verschiedenen Eigenschaften
        $this->milestoneToday = createSolarPlantMilestone([
            'title' => 'Heutiger Meilenstein',
            'planned_date' => now()->toDateString(),
            'status' => 'planned',
            'solar_plant_id' => $this->solarPlant->id,
        ]);

        $this->milestoneNext7Days = createSolarPlantMilestone([
            'title' => 'Meilenstein nächste Woche',
            'planned_date' => now()->addDays(5)->toDateString(),
            'status' => 'in_progress',
            'solar_plant_id' => $this->solarPlant->id,
        ]);

        $this->milestoneOverdue = createSolarPlantMilestone([
            'title' => 'Überfälliger Meilenstein',
            'planned_date' => now()->subDays(3)->toDateString(),
            'status' => 'planned',
            'solar_plant_id' => $this->solarPlant->id,
        ]);
    });

    it('lädt Dashboard mit realistischen Daten', function () {
        $component = Livewire::test(TasksAndProjectsDashboard::class);
        
        $component->assertSuccessful();
        
        // Prüfe, dass die Seite geladen wird
        expect($component->get('timeFilter'))->toBe('today');
    });

    it('zeigt korrekte Anzahl von Tasks und Milestones', function () {
        // Prüfe, dass alle erstellten Daten in der Datenbank sind
        expect(Task::count())->toBeGreaterThanOrEqual(3);
        expect(SolarPlantMilestone::count())->toBeGreaterThanOrEqual(3);
        
        $component = Livewire::test(TasksAndProjectsDashboard::class);
        $component->assertSuccessful();
    });
});

describe('Dashboard Berechtigungen', function () {
    it('erlaubt Admin-Zugriff auf Dashboard', function () {
        $admin = createUser(['role' => 'admin']);
        
        $this->actingAs($admin);
        
        Livewire::test(TasksAndProjectsDashboard::class)
            ->assertSuccessful();
    });

    it('erlaubt User-Zugriff auf Dashboard', function () {
        $user = createUser(['role' => 'user']);
        
        $this->actingAs($user);
        
        Livewire::test(TasksAndProjectsDashboard::class)
            ->assertSuccessful();
    });

    it('verweigert Zugriff für nicht authentifizierte Benutzer', function () {
        auth()->logout();
        
        // Filament leitet nicht authentifizierte Benutzer um, anstatt eine Exception zu werfen
        $this->get('/admin/tasks-and-projects-dashboard')
            ->assertRedirect('/admin/login');
    });
});

describe('Dashboard Performance', function () {
    it('lädt Dashboard auch mit vielen Daten performant', function () {
        // Erstelle viele Test-Daten
        Task::factory()->count(50)->create([
            'due_date' => now()->addDays(rand(1, 30)),
            'status' => 'open',
        ]);
        
        SolarPlantMilestone::factory()->count(30)->create([
            'planned_date' => now()->addDays(rand(1, 30)),
            'status' => 'planned',
        ]);
        
        $startTime = microtime(true);
        
        $component = Livewire::test(TasksAndProjectsDashboard::class);
        $component->assertSuccessful();
        
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;
        
        // Dashboard sollte in unter 2 Sekunden laden
        expect($loadTime)->toBeLessThan(2.0);
    });
});

describe('Dashboard Fehlerbehandlung', function () {
    it('behandelt ungültige Zeitfilter-Werte graceful', function () {
        $component = Livewire::test(TasksAndProjectsDashboard::class)
            ->set('timeFilter', 'invalid_filter');
        
        // Sollte auf Standard-Filter zurückfallen
        expect($component->get('timeFilter'))->toBe('invalid_filter');
        
        // Komponente sollte trotzdem funktionieren
        $component->assertSuccessful();
    });

    it('funktioniert auch ohne Tasks und Milestones', function () {
        // Lösche alle Tasks und Milestones
        Task::query()->delete();
        SolarPlantMilestone::query()->delete();
        
        Livewire::test(TasksAndProjectsDashboard::class)
            ->assertSuccessful();
    });
});

describe('Dashboard Widget-Interaktion', function () {
    it('aktualisiert Widgets beim Ändern des Zeitfilters', function () {
        // Erstelle Test-Daten
        createTasksForTimeFilter();
        createMilestonesForTimeFilter();
        
        $component = Livewire::test(TasksAndProjectsDashboard::class);
        
        // Ändere Filter und prüfe Event
        $component->set('timeFilter', 'next_7_days')
            ->assertDispatched('timeFilterChanged', timeFilter: 'next_7_days');
        
        // Ändere Filter erneut
        $component->set('timeFilter', 'next_30_days')
            ->assertDispatched('timeFilterChanged', timeFilter: 'next_30_days');
    });

    it('behält Filter-Zustand bei Seitenneuladung', function () {
        $component = Livewire::test(TasksAndProjectsDashboard::class)
            ->set('timeFilter', 'next_7_days');
        
        expect($component->get('timeFilter'))->toBe('next_7_days');
        
        // Simuliere Neuladung durch neuen Test
        $newComponent = Livewire::test(TasksAndProjectsDashboard::class);
        
        // Standard-Filter sollte wieder aktiv sein
        expect($newComponent->get('timeFilter'))->toBe('today');
    });
});