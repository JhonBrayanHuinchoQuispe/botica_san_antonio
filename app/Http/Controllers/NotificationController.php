<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Producto;

class NotificationController extends Controller
{
    /**
     * Obtener todas las notificaciones del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id() ?? 1; // Fallback para testing
        
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->filter(function ($notification) {
                $data = $notification->data ?? [];
                $pid = $data['producto_id'] ?? null;
                if ($pid === null) return true;
                return Producto::where('id', $pid)->exists();
            })
            ->values()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'priority' => $notification->priority,
                    'is_read' => $notification->isRead(),
                    'time_ago' => $notification->time_ago,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'data' => $notification->data
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'count' => $notifications->count()
        ]);
    }

    /**
     * Obtener solo las notificaciones no leídas
     */
    public function unread(Request $request): JsonResponse
    {
        $userId = Auth::id() ?? 1;
        
        $notifications = Notification::where('user_id', $userId)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->filter(function ($notification) {
                $data = $notification->data ?? [];
                $pid = $data['producto_id'] ?? null;
                if ($pid === null) return true;
                return Producto::where('id', $pid)->exists();
            })
            ->values()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'priority' => $notification->priority,
                    'time_ago' => $notification->time_ago,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'data' => $notification->data
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Contar notificaciones no leídas
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = Auth::id() ?? 1;
        
        $count = Notification::where('user_id', $userId)
            ->unread()
            ->get()
            ->filter(function ($notification) {
                $data = $notification->data ?? [];
                $pid = $data['producto_id'] ?? null;
                if ($pid === null) return true;
                return Producto::where('id', $pid)->exists();
            })
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Marcar una notificación como leída
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $userId = Auth::id() ?? 1;
        
        $notification = Notification::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = Auth::id() ?? 1;
        
        $updated = Notification::where('user_id', $userId)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Se marcaron {$updated} notificaciones como leídas"
        ]);
    }

    /**
     * Eliminar una notificación
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $userId = Auth::id() ?? 1;
        
        $notification = Notification::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada'
        ]);
    }

    /**
     * Obtener notificaciones por tipo
     */
    public function getByType(Request $request, $type): JsonResponse
    {
        $userId = Auth::id() ?? 1;
        
        $notifications = Notification::where('user_id', $userId)
            ->ofType($type)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->filter(function ($notification) {
                $data = $notification->data ?? [];
                $pid = $data['producto_id'] ?? null;
                if ($pid === null) return true;
                return Producto::where('id', $pid)->exists();
            })
            ->values()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'priority' => $notification->priority,
                    'is_read' => $notification->isRead(),
                    'time_ago' => $notification->time_ago,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'data' => $notification->data
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }
}
