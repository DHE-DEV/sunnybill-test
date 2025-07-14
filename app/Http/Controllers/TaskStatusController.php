<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TaskStatusController extends Controller
{
    /**
     * Update task status via AJAX for drag & drop
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(['open', 'in_progress', 'waiting_external', 'waiting_internal', 'completed', 'cancelled'])
            ]
        ]);

        try {
            $oldStatus = $task->status;
            $newStatus = $request->input('status');

            // Status aktualisieren
            $task->update([
                'status' => $newStatus,
                'completed_at' => $newStatus === 'completed' ? now() : null,
            ]);

            // Log für Debugging (optional)
            \Log::info("Task status updated", [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status erfolgreich aktualisiert',
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Failed to update task status", [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Aktualisieren des Status: ' . $e->getMessage()
            ], 500);
        }
    }
}