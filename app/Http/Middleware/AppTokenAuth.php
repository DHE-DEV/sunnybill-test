<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AppToken;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AppTokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        // Prüfe auf Bearer Token im Authorization Header
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token fehlt oder ungültiges Format'
            ], 401);
        }

        // Extrahiere den Token
        $token = substr($authHeader, 7); // Entferne "Bearer "
        
        if (empty($token)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token ist leer'
            ], 401);
        }

        // Finde den Token in der Datenbank
        $appToken = AppToken::findByToken($token);
        
        if (!$appToken) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token nicht gefunden'
            ], 401);
        }

        // Prüfe ob Token gültig ist
        if (!$appToken->isValid()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token ist ungültig, deaktiviert oder abgelaufen'
            ], 401);
        }

        // Prüfe Berechtigungen falls angegeben
        if (!empty($abilities)) {
            $hasRequiredAbility = false;
            foreach ($abilities as $ability) {
                if ($appToken->hasAbility($ability)) {
                    $hasRequiredAbility = true;
                    break;
                }
            }
            
            if (!$hasRequiredAbility) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Unzureichende Berechtigungen für diese Aktion',
                    'required_abilities' => $abilities,
                    'token_abilities' => $appToken->abilities
                ], 403);
            }
        }

        // Authentifiziere den User
        Auth::setUser($appToken->user);
        
        // Markiere Token als verwendet
        $appToken->markAsUsed();
        
        // Füge Token-Informationen zur Anfrage hinzu
        $request->merge([
            'app_token' => $appToken,
            'app_token_abilities' => $appToken->abilities,
        ]);

        return $next($request);
    }
}
