<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupportTicketService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function createTicket(User $user, array $data): SupportTicket
    {
        return DB::transaction(function () use ($user, $data) {
            $ticket = SupportTicket::create([
                'uuid' => Str::uuid(),
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'ticket_number' => SupportTicket::generateTicketNumber($user->company_id),
                'subject' => $data['subject'],
                'category' => $data['category'] ?? 'general',
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'open',
                'last_message_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->addMessage($ticket, $user, $data['message'], false);

            $this->notifySuperAdmins(
                $ticket,
                'New support ticket',
                "{$user->name} opened ticket {$ticket->ticket_number}: {$ticket->subject}"
            );

            return $ticket->fresh(['messages.user', 'company', 'user']);
        });
    }

    public function addMessage(SupportTicket $ticket, User $user, string $message, bool $isInternal = false): SupportTicketMessage
    {
        $msg = SupportTicketMessage::create([
            'uuid' => Str::uuid(),
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $message,
            'is_internal' => $isInternal,
        ]);

        $ticket->update([
            'last_message_at' => now(),
            'updated_by' => $user->id,
            'status' => $this->nextStatusAfterMessage($ticket, $user, $isInternal),
        ]);

        if (!$isInternal) {
            if ($user->isSuperAdmin()) {
                $this->notificationService->notifyUser(
                    $ticket->user_id,
                    $ticket->company_id,
                    'support.reply',
                    'Support replied to your ticket',
                    "Ticket {$ticket->ticket_number}: new message from support.",
                    [
                        'link_module' => 'support-tickets',
                        'link_id' => (string) $ticket->id,
                        'icon' => 'bi-headset',
                        'color' => 'text-primary',
                    ]
                );
            } else {
                $this->notifySuperAdmins(
                    $ticket,
                    'Ticket updated',
                    "{$user->name} replied on {$ticket->ticket_number}"
                );
            }
        }

        return $msg->load('user');
    }

    public function updateStatus(SupportTicket $ticket, string $status, ?User $actor = null, ?int $assigneeId = null): SupportTicket
    {
        $updates = [
            'status' => $status,
            'updated_by' => $actor?->id,
        ];

        if ($assigneeId) {
            $updates['assigned_to'] = $assigneeId;
        }

        if (in_array($status, ['resolved', 'closed'], true)) {
            $updates['closed_at'] = now();
        }

        $ticket->update($updates);

        $this->notificationService->notifyUser(
            $ticket->user_id,
            $ticket->company_id,
            'support.status',
            'Ticket status updated',
            "Ticket {$ticket->ticket_number} is now " . str_replace('_', ' ', $status) . '.',
            [
                'link_module' => 'support-tickets',
                'link_id' => (string) $ticket->id,
                'icon' => 'bi-ticket-detailed',
                'color' => 'text-info',
            ]
        );

        return $ticket->fresh();
    }

    public function listForCompany(int $companyId, array $filters = [])
    {
        $query = SupportTicket::with(['user', 'assignee'])
            ->where('company_id', $companyId)
            ->orderByDesc('last_message_at');

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'resolved') {
                $query->whereIn('status', ['resolved', 'closed']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function listForPlatform(array $filters = [])
    {
        $query = SupportTicket::with(['user', 'company', 'assignee'])
            ->orderByDesc('last_message_at');

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'resolved') {
                $query->whereIn('status', ['resolved', 'closed']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    protected function nextStatusAfterMessage(SupportTicket $ticket, User $user, bool $isInternal): string
    {
        if ($isInternal) {
            return $ticket->status;
        }

        if ($user->isSuperAdmin()) {
            return $ticket->status === 'open' ? 'in_progress' : $ticket->status;
        }

        return in_array($ticket->status, ['waiting_on_customer', 'resolved', 'closed'], true)
            ? 'in_progress'
            : $ticket->status;
    }

    protected function notifySuperAdmins(SupportTicket $ticket, string $title, string $message): void
    {
        $superAdmins = User::whereHas('roles', fn ($q) => $q->where('slug', 'superadmin'))->get();

        foreach ($superAdmins as $admin) {
            $this->notificationService->notifyUser(
                $admin->id,
                $ticket->company_id,
                'support.ticket',
                $title,
                $message,
                [
                    'link_module' => 'platform-support-tickets',
                    'link_id' => (string) $ticket->id,
                    'icon' => 'bi-headset',
                    'color' => 'text-warning',
                    'priority' => 'high',
                ]
            );
        }
    }
}
