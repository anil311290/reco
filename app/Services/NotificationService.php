<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Str;

class NotificationService
{
    public function create(array $data): Notification
    {
        return Notification::create(array_merge([
            'uuid' => Str::uuid(),
            'channel' => 'in_app',
            'priority' => 'normal',
            'is_read' => false,
            'sent_at' => now(),
            'created_by' => auth()->id(),
            'created_by_ip' => request()->ip(),
        ], $data));
    }

    public function notifyUser(
        int $userId,
        int $companyId,
        string $type,
        string $title,
        string $message,
        array $extra = []
    ): Notification {
        return $this->create(array_merge([
            'user_id' => $userId,
            'company_id' => $companyId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ], $extra));
    }

    public function getForUser(int $userId, int $limit = 20)
    {
        return Notification::forUser($userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::forUser($userId)->unread()->count();
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function resolveUrl(Notification $notification): string
    {
        return match ($notification->link_module) {
            'support-tickets', 'platform-support-tickets' => route('admin.support-tickets.show', $notification->link_id),
            default => route('admin.notifications.index'),
        };
    }
}
