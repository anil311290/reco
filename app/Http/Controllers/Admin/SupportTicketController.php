<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    protected SupportTicketService $supportTicketService;

    public function __construct(SupportTicketService $supportTicketService)
    {
        $this->supportTicketService = $supportTicketService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();

        if ($isSuperAdmin) {
            $tickets = $this->supportTicketService->listForPlatform($request->only(['status', 'company_id']));
            $companies = \App\Models\Company::orderBy('name')->get(['id', 'name']);
            $statsQuery = SupportTicket::query();
        } else {
            $tickets = $this->supportTicketService->listForCompany($user->company_id, $request->only(['status']));
            $companies = collect();
            $statsQuery = SupportTicket::where('company_id', $user->company_id);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'open' => (clone $statsQuery)->where('status', 'open')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'waiting' => (clone $statsQuery)->where('status', 'waiting_on_customer')->count(),
            'resolved' => (clone $statsQuery)->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        return view('admin.support-tickets.index', compact('tickets', 'stats', 'isSuperAdmin', 'companies'));
    }

    public function create()
    {
        if (auth()->user()->isSuperAdmin()) {
            abort(403, 'Super admins manage tickets from the platform inbox.');
        }

        return view('admin.support-tickets.create');
    }

    public function store(Request $request)
    {
        if ($request->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'category' => 'nullable|in:general,billing,technical,feature,other',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $ticket = $this->supportTicketService->createTicket($request->user(), $validated);

        return redirect()
            ->route('admin.support-tickets.show', $ticket->id)
            ->with('success', 'Support ticket created successfully.');
    }

    public function show(Request $request, int $id)
    {
        $ticket = $this->findTicket($request, $id);
        $isSuperAdmin = $request->user()->isSuperAdmin();
        $messages = $isSuperAdmin ? $ticket->messages : $ticket->publicMessages;
        $messages->load('user');
        $agents = $isSuperAdmin
            ? User::whereHas('roles', fn ($q) => $q->where('slug', 'superadmin'))->get(['id', 'name'])
            : collect();

        return view('admin.support-tickets.show', compact('ticket', 'messages', 'isSuperAdmin', 'agents'));
    }

    public function reply(Request $request, int $id)
    {
        $ticket = $this->findTicket($request, $id);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'is_internal' => 'nullable|boolean',
        ]);

        $isInternal = $request->boolean('is_internal') && $request->user()->isSuperAdmin();

        $this->supportTicketService->addMessage(
            $ticket,
            $request->user(),
            $validated['message'],
            $isInternal
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Message sent.']);
        }

        return back()->with('success', 'Message sent.');
    }

    public function updateStatus(Request $request, int $id)
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403);
        }

        $ticket = $this->findTicket($request, $id);

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,waiting_on_customer,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $this->supportTicketService->updateStatus(
            $ticket,
            $validated['status'],
            $request->user(),
            $validated['assigned_to'] ?? null
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Ticket updated.']);
        }

        return back()->with('success', 'Ticket status updated.');
    }

    protected function findTicket(Request $request, int $id): SupportTicket
    {
        $query = SupportTicket::with(['company', 'user', 'assignee']);

        if (!$request->user()->isSuperAdmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        return $query->findOrFail($id);
    }
}
