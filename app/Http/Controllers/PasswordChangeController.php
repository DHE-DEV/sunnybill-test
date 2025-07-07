<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordChangeController extends Controller
{
    /**
     * Show the password change form.
     */
    public function show()
    {
        return view('auth.change-password');
    }

    /**
     * Handle the password change request.
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Das aktuelle Passwort ist erforderlich.',
            'current_password.current_password' => 'Das aktuelle Passwort ist nicht korrekt.',
            'password.required' => 'Das neue Passwort ist erforderlich.',
            'password.confirmed' => 'Die Passwort-Bestätigung stimmt nicht überein.',
            'password.min' => 'Das Passwort muss mindestens :min Zeichen lang sein.',
        ]);

        $user = $request->user();
        
        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);
        
        // Mark password as changed
        $user->markPasswordAsChanged();

        return redirect()->route('filament.admin.pages.dashboard')
            ->with('success', 'Ihr Passwort wurde erfolgreich geändert.');
    }
}