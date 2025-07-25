<?php

use App\Models\User;
use Filament\Http\Livewire\Auth\Login;
use Livewire\Livewire;

describe('Filament Authentication', function () {
    it('kann sich mit gültigen Anmeldedaten anmelden', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'test@example.com',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertRedirect('/admin'); // Oder Ihre Filament-Admin-Route
    });
    
    it('verhindert Anmeldung mit ungültigen Anmeldedaten', function () {
        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'wrong@example.com',
                'password' => 'wrongpassword',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
    });
    
    it('erfordert E-Mail und Passwort', function () {
        Livewire::test(Login::class)
            ->call('authenticate')
            ->assertHasFormErrors(['email', 'password']);
    });
    
    it('kann sich abmelden', function () {
        $user = createUser();
        
        $this->actingAs($user)
            ->post('/admin/logout')
            ->assertRedirect('/admin/login');
        
        $this->assertGuest();
    });
    
    it('leitet nicht authentifizierte Benutzer zur Anmeldung weiter', function () {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    });
    
    it('kann auf Admin-Panel zugreifen nach erfolgreicher Anmeldung', function () {
        $user = createAdminUser();
        
        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful();
    });
});

describe('Filament User Permissions', function () {
    it('nur Admin-Benutzer können auf bestimmte Ressourcen zugreifen', function () {
        $regularUser = createUser(['is_admin' => false]);
        $adminUser = createAdminUser(['is_admin' => true]);
        
        // Regulärer Benutzer wird abgelehnt
        $this->actingAs($regularUser)
            ->get('/admin/customers')
            ->assertForbidden();
        
        // Admin-Benutzer hat Zugriff
        $this->actingAs($adminUser)
            ->get('/admin/customers')
            ->assertSuccessful();
    });
});