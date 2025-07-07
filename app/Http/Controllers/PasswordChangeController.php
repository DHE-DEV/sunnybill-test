<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class PasswordChangeController extends Controller
{
    /**
     * Show the password change form
     */
    public function show(Request $request)
    {
        $user = Auth::user();
        
        // Prüfe ob der Benutzer ein temporäres Passwort hat oder eine Passwort-Änderung erforderlich ist
        if (!$user->needsPasswordChange() && !$user->hasTemporaryPassword()) {
            return redirect('/admin')->with('message', 'Keine Passwort-Änderung erforderlich.');
        }

        return view('auth.change-password', [
            'user' => $user,
            'hasTemporaryPassword' => $user->hasTemporaryPassword(),
            'temporaryPassword' => $user->getTemporaryPasswordForEmail()
        ]);
    }

    /**
     * Update the user's password
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        // Wenn der Benutzer ein temporäres Passwort hat, muss er es eingeben
        if ($user->hasTemporaryPassword()) {
            $rules['current_password'] = ['required'];
        }

        $request->validate($rules);

        // Prüfe das aktuelle/temporäre Passwort
        if ($user->hasTemporaryPassword()) {
            if ($request->current_password !== $user->getTemporaryPasswordForEmail()) {
                return back()->withErrors([
                    'current_password' => 'Das eingegebene temporäre Passwort ist nicht korrekt.'
                ]);
            }
        }

        // Aktualisiere das Passwort
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Markiere Passwort als geändert und lösche temporäres Passwort
        $user->markPasswordAsChanged();

        return redirect('/admin')->with('status', 'Passwort erfolgreich geändert! Sie können sich jetzt normal anmelden.');
    }

    /**
     * Show password change form for users with temporary passwords (without authentication)
     */
    public function showForTemporaryPassword(Request $request, $userId, $token)
    {
        // Finde den Benutzer
        $user = \App\Models\User::findOrFail($userId);
        
        // Prüfe ob der Token gültig ist (einfache Implementierung)
        $expectedToken = hash('sha256', $user->id . $user->email . $user->created_at);
        
        if (!hash_equals($token, $expectedToken)) {
            abort(403, 'Ungültiger Token.');
        }

        // Prüfe ob der Benutzer ein temporäres Passwort hat
        if (!$user->hasTemporaryPassword()) {
            return redirect('/admin/login')->with('message', 'Keine Passwort-Änderung erforderlich.');
        }

        return view('auth.change-password-temporary', [
            'user' => $user,
            'token' => $token,
            'temporaryPassword' => $user->getTemporaryPasswordForEmail()
        ]);
    }

    /**
     * Update password for users with temporary passwords (without authentication)
     */
    public function updateTemporaryPassword(Request $request, $userId, $token)
    {
        // Finde den Benutzer
        $user = \App\Models\User::findOrFail($userId);
        
        // Prüfe ob der Token gültig ist
        $expectedToken = hash('sha256', $user->id . $user->email . $user->created_at);
        
        if (!hash_equals($token, $expectedToken)) {
            abort(403, 'Ungültiger Token.');
        }

        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Prüfe das temporäre Passwort
        if ($request->current_password !== $user->getTemporaryPasswordForEmail()) {
            return back()->withErrors([
                'current_password' => 'Das eingegebene temporäre Passwort ist nicht korrekt.'
            ]);
        }

        // Aktualisiere das Passwort
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Markiere Passwort als geändert und lösche temporäres Passwort
        $user->markPasswordAsChanged();

        // Logge den Benutzer automatisch ein
        Auth::login($user);

        // Prüfe ob der Benutzer Zugriff auf das Admin-Panel hat
        try {
            $panel = \Filament\Facades\Filament::getCurrentPanel();
            if ($user->canAccessPanel($panel)) {
                return redirect('/admin')->with('status', 'Passwort erfolgreich geändert! Sie sind jetzt angemeldet.');
            }
        } catch (\Exception $e) {
            // Fallback falls Panel-Check fehlschlägt
        }

        // Für Benutzer ohne Admin-Zugriff: Erfolgsseite anzeigen
        return view('auth.password-changed-success', [
            'user' => $user,
            'message' => 'Passwort erfolgreich geändert! Sie sind jetzt angemeldet.'
        ]);
    }
}
