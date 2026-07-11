<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 20);
        $notifications = $this->notificationService->getForUser($request->user()->id, $limit);

        return ResponseHelper::success([
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount($request->user()->id),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return ResponseHelper::success([
            'unread_count' => $this->notificationService->getUnreadCount($request->user()->id),
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $ok = $this->notificationService->markAsRead($id, $request->user()->id);

        if (!$ok) {
            return ResponseHelper::notFound('Notification not found');
        }

        return ResponseHelper::success([
            'unread_count' => $this->notificationService->getUnreadCount($request->user()->id),
        ], 'Notification marked as read');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return ResponseHelper::success([
            'updated' => $count,
            'unread_count' => 0,
        ], 'All notifications marked as read');
    }
}
