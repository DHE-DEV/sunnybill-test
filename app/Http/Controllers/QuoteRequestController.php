<?php

namespace App\Http\Controllers;

use App\Mail\QuoteRequestMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class QuoteRequestController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firma' => 'required|string|max:255',
            'ansprechpartner' => 'required|string|max:255',
            'strasse' => 'required|string|max:255',
            'plz' => 'required|string|max:10',
            'ort' => 'required|string|max:255',
            'telefon' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'solaranlagen' => 'required|integer|min:1',
            'beteiligungen' => 'required|integer|min:0',
            'benutzer' => 'required|integer|min:1',
            'modul_aufgaben' => 'nullable|boolean',
            'modul_projekte' => 'nullable|boolean',
            'modul_dokumente' => 'nullable|boolean',
            'zahlungsweise' => 'required|in:monthly,yearly',
            'gesamtpreis' => 'required|string|max:100',
        ]);

        Mail::to('info@dhe.de')->send(new QuoteRequestMail($validated));

        return response()->json(['success' => true, 'message' => 'Ihre Anfrage wurde erfolgreich gesendet.']);
    }
}
