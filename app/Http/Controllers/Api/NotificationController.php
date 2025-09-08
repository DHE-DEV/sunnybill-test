<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notification count for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function count(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $unreadCount = $user->unread_notifications_count;

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Benachrichtigungen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all notifications for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $limit = $request->get('limit', 50);
            $unreadOnly = $request->boolean('unread_only', false);

            $query = $user->notifications()
                ->orderBy('created_at', 'desc')
                ->limit($limit);

            if ($unreadOnly) {
                $query->whereNull('read_at');
            }

            $notifications = $query->get();

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'total_count' => $notifications->count(),
                'unread_count' => $user->unread_notifications_count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Benachrichtigungen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a notification as read
     *
     * @param Request $request
     * @param int $notificationId
     * @return JsonResponse
     */
    public function markAsRead(Request $request, int $notificationId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $notification = $user->notifications()->findOrFail($notificationId);
            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Benachrichtigung als gelesen markiert',
                'unread_count' => $user->fresh()->unread_notifications_count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Markieren der Benachrichtigung: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $user->markAllNotificationsAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Alle Benachrichtigungen als gelesen markiert',
                'unread_count' => 0
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Markieren aller Benachrichtigungen: ' . $e->getMessage()
            ], 500);
        }
    }
}
