<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $this->notificationService->getForUser($user->id, 50);

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $this->notificationService->getUnreadCount($user->id),
        ]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $ok = $this->notificationService->markAsRead($id, $request->user()->id);

        if (!$ok) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'unread_count' => $this->notificationService->getUnreadCount($request->user()->id),
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'success' => true,
            'updated' => $count,
            'unread_count' => 0,
        ]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread_count' => $this->notificationService->getUnreadCount($request->user()->id),
        ]);
    }

    public function feed(Request $request)
    {
        $user = $request->user();
        $notifications = $this->notificationService->getForUser($user->id, 8);

        return response()->json([
            'unread_count' => $this->notificationService->getUnreadCount($user->id),
            'notifications' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'icon' => $n->icon ?? 'bi-bell',
                'color' => $n->color ?? 'text-primary',
                'is_read' => (bool) $n->is_read,
                'url' => $this->notificationService->resolveUrl($n),
                'created_at' => $n->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
