<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Prüfe ob Benutzer angemeldet ist und Passwort-Wechsel erforderlich ist
        if ($user && $user->needsPasswordChange()) {
            // Erlaube Zugriff auf Passwort-Wechsel-Routen und Logout
            $allowedRoutes = [
                'filament.admin.auth.password-reset.request',
                'filament.admin.auth.password-reset.reset',
                'filament.admin.auth.logout',
                'password.change',
                'password.update',
                'logout',
            ];
            
            $currentRoute = $request->route()?->getName();
            
            // Erlaube auch AJAX-Requests für Filament
            if ($request->ajax() || $request->wantsJson()) {
                return $next($request);
            }
            
            // Wenn nicht auf erlaubter Route, leite zur Passwort-Änderung weiter
            if (!in_array($currentRoute, $allowedRoutes) && !str_contains($currentRoute ?? '', 'password')) {
                return redirect()->route('password.change')
                    ->with('warning', 'Sie müssen Ihr Passwort ändern, bevor Sie fortfahren können.');
            }
        }
        
        return $next($request);
    }
}