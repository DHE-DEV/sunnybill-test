<?php

namespace App\Http\Controllers;

use App\Models\UserTablePreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InfolistStateController extends Controller
{
    public function save(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'table_name' => 'required|string',
            'section_id' => 'required|string',
            'collapsed' => 'required|boolean',
        ]);

        $userId = Auth::id();
        $tableName = $validated['table_name'];
        $sectionId = $validated['section_id'];
        $collapsed = $validated['collapsed'];

        // Hole aktuelle Infolist-Zustände
        $currentState = UserTablePreference::getInfolistState($userId, $tableName) ?? [];
        
        // Aktualisiere den Zustand für diese Section
        $currentState[$sectionId] = $collapsed;

        // Speichere den aktualisierten Zustand
        UserTablePreference::saveInfolistState($userId, $tableName, $currentState);

        return response()->json(['success' => true]);
    }
}
