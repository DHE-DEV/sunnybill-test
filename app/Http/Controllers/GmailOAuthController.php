<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class GmailOAuthController extends Controller
{
    /**
     * Behandelt den OAuth2-Callback von Google
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $code = $request->get('code');
            $error = $request->get('error');
            
            if ($error) {
                return redirect('/admin/company-settings')
                    ->with('error', 'OAuth2-Autorisierung abgebrochen: ' . $error);
            }
            
            if (!$code) {
                return redirect('/admin/company-settings')
                    ->with('error', 'Kein Autorisierungscode erhalten.');
            }
            
            $gmailService = new GmailService();
            $redirectUri = url('/admin/gmail/oauth/callback');
            
            // Code gegen Tokens tauschen
            $tokens = $gmailService->exchangeCodeForTokens($code, $redirectUri);
            
            return redirect('/admin/company-settings')
                ->with('success', 'Gmail-Autorisierung erfolgreich! Sie können jetzt E-Mails synchronisieren.');
                
        } catch (\Exception $e) {
            \Log::error('Gmail OAuth Callback Error: ' . $e->getMessage());
            
            return redirect('/admin/company-settings')
                ->with('error', 'Fehler bei der Gmail-Autorisierung: ' . $e->getMessage());
        }
    }
    
    /**
     * Startet den OAuth2-Autorisierungsprozess
     */
    public function authorize(): RedirectResponse
    {
        try {
            $settings = CompanySetting::current();
            
            if (!$settings->isGmailEnabled() || !$settings->getGmailClientId() || !$settings->getGmailClientSecret()) {
                return redirect('/admin/company-settings')
                    ->with('error', 'Gmail ist nicht konfiguriert. Bitte konfigurieren Sie zuerst Client ID und Client Secret.');
            }
            
            $gmailService = new GmailService();
            $redirectUri = url('/admin/gmail/oauth/callback');
            $authUrl = $gmailService->getAuthorizationUrl($redirectUri);
            
            return redirect()->away($authUrl);
            
        } catch (\Exception $e) {
            \Log::error('Gmail OAuth Authorize Error: ' . $e->getMessage());
            
            return redirect('/admin/company-settings')
                ->with('error', 'Fehler beim Starten der Gmail-Autorisierung: ' . $e->getMessage());
        }
    }
    
    /**
     * Widerruft den Gmail-Zugriff
     */
    public function revoke(): RedirectResponse
    {
        try {
            $settings = CompanySetting::current();
            $settings->clearGmailTokens();
            
            return redirect('/admin/company-settings')
                ->with('success', 'Gmail-Zugriff wurde erfolgreich widerrufen.');
                
        } catch (\Exception $e) {
            \Log::error('Gmail OAuth Revoke Error: ' . $e->getMessage());
            
            return redirect('/admin/company-settings')
                ->with('error', 'Fehler beim Widerrufen des Gmail-Zugriffs: ' . $e->getMessage());
        }
    }
    
    /**
     * Testet die Gmail-Verbindung
     */
    public function test(): RedirectResponse
    {
        try {
            $gmailService = new GmailService();
            $result = $gmailService->testConnection();
            
            if ($result['success']) {
                return redirect('/admin/company-settings')
                    ->with('success', 'Gmail-Verbindung erfolgreich! Verbunden mit: ' . $result['email']);
            } else {
                return redirect('/admin/company-settings')
                    ->with('error', 'Gmail-Verbindung fehlgeschlagen: ' . $result['error']);
            }
            
        } catch (\Exception $e) {
            \Log::error('Gmail Test Connection Error: ' . $e->getMessage());
            
            return redirect('/admin/company-settings')
                ->with('error', 'Fehler beim Testen der Gmail-Verbindung: ' . $e->getMessage());
        }
    }
}
