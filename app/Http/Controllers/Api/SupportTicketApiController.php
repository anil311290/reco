<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Helpers\ResponseHelper;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketApiController extends Controller
{
    protected SupportTicketService $supportTicketService;

    public function __construct(SupportTicketService $supportTicketService)
    {
        $this->supportTicketService = $supportTicketService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $tickets = $this->supportTicketService->listForPlatform($request->only(['status', 'company_id', 'per_page']));
        } else {
            $tickets = $this->supportTicketService->listForCompany($user->company_id, $request->only(['status', 'per_page']));
        }

        return ResponseHelper::success($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'category' => 'nullable|in:general,billing,technical,feature,other',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $ticket = $this->supportTicketService->createTicket($request->user(), $validated);

        return ResponseHelper::success($ticket, 'Support ticket created', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $ticket = $this->findTicket($request, $id);

        if (!$ticket) {
            return ResponseHelper::notFound('Ticket not found');
        }

        $ticket->load(['messages.user', 'company', 'user', 'assignee']);

        if (!$request->user()->isSuperAdmin()) {
            $ticket->setRelation('messages', $ticket->messages->where('is_internal', false)->values());
        }

        return ResponseHelper::success($ticket);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $ticket = $this->findTicket($request, $id);

        if (!$ticket) {
            return ResponseHelper::notFound('Ticket not found');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'is_internal' => 'nullable|boolean',
        ]);

        $isInternal = $request->boolean('is_internal') && $request->user()->isSuperAdmin();

        $message = $this->supportTicketService->addMessage(
            $ticket,
            $request->user(),
            $validated['message'],
            $isInternal
        );

        return ResponseHelper::success($message, 'Message sent');
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $ticket = $this->findTicket($request, $id);

        if (!$ticket) {
            return ResponseHelper::notFound('Ticket not found');
        }

        if (!$request->user()->isSuperAdmin()) {
            return ResponseHelper::error('Only support staff can update ticket status', 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,waiting_on_customer,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $updated = $this->supportTicketService->updateStatus(
            $ticket,
            $validated['status'],
            $request->user(),
            $validated['assigned_to'] ?? null
        );

        return ResponseHelper::success($updated, 'Ticket status updated');
    }

    protected function findTicket(Request $request, int $id): ?SupportTicket
    {
        $query = SupportTicket::query();

        if (!$request->user()->isSuperAdmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        return $query->find($id);
    }
}
