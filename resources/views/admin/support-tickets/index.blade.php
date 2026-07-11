@extends('layouts.app')

@section('title', $isSuperAdmin ? 'Support Inbox' : 'Help & Support')

@section('content')
@php
    $statusLabels = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'waiting_on_customer' => 'Waiting on You',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];
    $statusBadges = [
        'open' => 'bg-primary-subtle text-primary-emphasis',
        'in_progress' => 'bg-warning-subtle text-warning-emphasis',
        'waiting_on_customer' => 'bg-info-subtle text-info-emphasis',
        'resolved' => 'bg-success-subtle text-success-emphasis',
        'closed' => 'bg-secondary-subtle text-secondary-emphasis',
    ];
    $priorityBadges = [
        'low' => 'bg-light text-muted border',
        'normal' => 'bg-secondary-subtle text-secondary-emphasis',
        'high' => 'bg-warning-subtle text-warning-emphasis',
        'urgent' => 'bg-danger-subtle text-danger-emphasis',
    ];
    $categoryIcons = [
        'general' => 'bi-question-circle',
        'billing' => 'bi-credit-card',
        'technical' => 'bi-tools',
        'feature' => 'bi-lightbulb',
        'other' => 'bi-three-dots',
    ];
    $activeStatus = request('status');
    $statCards = [
        ['key' => '', 'label' => 'All Tickets', 'value' => $stats['total'], 'icon' => 'bi-inbox', 'tone' => 'primary'],
        ['key' => 'open', 'label' => 'Open', 'value' => $stats['open'], 'icon' => 'bi-envelope-open', 'tone' => 'primary'],
        ['key' => 'in_progress', 'label' => 'In Progress', 'value' => $stats['in_progress'], 'icon' => 'bi-arrow-repeat', 'tone' => 'warning'],
        ['key' => 'waiting_on_customer', 'label' => $isSuperAdmin ? 'Waiting on Customer' : 'Awaiting Reply', 'value' => $stats['waiting'], 'icon' => 'bi-hourglass-split', 'tone' => 'info'],
        ['key' => 'resolved', 'label' => 'Resolved / Closed', 'value' => $stats['resolved'], 'icon' => 'bi-check-circle', 'tone' => 'success'],
    ];
@endphp

<div class="container-fluid px-0">
    {{-- Page header --}}
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-body p-4 p-lg-5 support-hero">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge rounded-pill support-hero-badge mb-3">
                        <i class="bi bi-headset me-1"></i>
                        {{ $isSuperAdmin ? 'Platform Support' : 'Customer Support' }}
                    </span>
                    <h2 class="mb-2 support-hero-title">{{ $isSuperAdmin ? 'Support Inbox' : 'Help & Support' }}</h2>
                    <p class="mb-0 support-hero-subtitle">
                        @if($isSuperAdmin)
                            Review and respond to support tickets from all tenant companies in one place.
                        @else
                            Raise a ticket when you need help. Our team will reply in the same thread and you will get notified.
                        @endif
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    @if(!$isSuperAdmin)
                    <a href="{{ route('admin.support-tickets.create') }}" class="btn support-hero-btn fw-semibold">
                        <i class="bi bi-plus-circle me-2"></i>New Ticket
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        @foreach($statCards as $card)
        @php
            $isActive = ($activeStatus === $card['key']) || ($card['key'] === '' && empty($activeStatus));
            $query = request()->except('page', 'status');
            if ($card['key'] !== '') {
                $query['status'] = $card['key'];
            }
        @endphp
        <div class="col-xxl col-md-4 col-6">
            <a href="{{ route('admin.support-tickets.index', $query) }}"
               class="text-decoration-none d-block h-100 support-stat-link {{ $isActive ? 'is-active' : '' }}">
                <div class="card border-0 shadow-sm h-100 support-stat-card">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="rounded-3 d-inline-flex align-items-center justify-content-center support-stat-icon tone-{{ $card['tone'] }}">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="small text-uppercase fw-semibold support-stat-label">{{ $card['label'] }}</div>
                            <div class="fs-4 fw-bold support-stat-value mb-0">{{ $card['value'] }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                @if($isSuperAdmin)
                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Company</label>
                    <select name="company_id" class="form-select">
                        <option value="">All companies</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($activeStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-auto col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i>Apply Filters
                    </button>
                    <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tickets table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">Ticket List</h5>
                    <p class="text-muted small mb-0">
                        {{ $tickets->total() }} ticket{{ $tickets->total() === 1 ? '' : 's' }}
                        @if($activeStatus)
                            · filtered by {{ $statusLabels[$activeStatus] ?? ucwords(str_replace('_', ' ', $activeStatus)) }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="card-body pt-3">
            @if($tickets->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle support-tickets-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 140px;">Ticket</th>
                            @if($isSuperAdmin)
                            <th style="width: 160px;">Company</th>
                            @endif
                            <th>Subject</th>
                            <th style="width: 120px;">Category</th>
                            <th style="width: 100px;">Priority</th>
                            <th style="width: 130px;">Status</th>
                            <th style="width: 160px;">Last Update</th>
                            <th style="width: 110px;" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                        <tr>
                            <td>
                                <div class="fw-semibold text-primary">{{ $ticket->ticket_number }}</div>
                                @if($isSuperAdmin && $ticket->user)
                                <div class="small text-muted">{{ $ticket->user->name }}</div>
                                @endif
                            </td>
                            @if($isSuperAdmin)
                            <td>
                                <div class="fw-medium">{{ $ticket->company->name ?? '—' }}</div>
                            </td>
                            @endif
                            <td>
                                <div class="support-ticket-subject">{{ $ticket->subject }}</div>
                                @if($ticket->assignee)
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-person-check me-1"></i>{{ $ticket->assignee->name }}
                                </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill support-category-badge">
                                    <i class="bi {{ $categoryIcons[$ticket->category] ?? 'bi-tag' }} me-1"></i>
                                    {{ ucfirst($ticket->category) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $priorityBadges[$ticket->priority] ?? 'bg-secondary' }}">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $statusBadges[$ticket->status] ?? 'bg-secondary' }}">
                                    {{ $statusLabels[$ticket->status] ?? ucwords(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </td>
                            <td>
                                <div class="small">@istDateTime($ticket->last_message_at ?? $ticket->created_at)</div>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.support-tickets.show', $ticket->id) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-chat-dots me-1"></i>Open
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="support-empty-state text-center py-5 px-3">
                <div class="support-empty-icon mx-auto mb-3">
                    <i class="bi bi-headset"></i>
                </div>
                <h5 class="mb-2">No support tickets found</h5>
                <p class="text-muted mb-4 mx-auto" style="max-width: 420px;">
                    @if($activeStatus || request('company_id'))
                        No tickets match your current filters. Try resetting filters or choose a different status.
                    @elseif($isSuperAdmin)
                        When tenant admins raise support tickets, they will appear here for your team to respond.
                    @else
                        You have not created any support tickets yet. Tell us about billing, technical issues, or feature requests.
                    @endif
                </p>
                @if(!$isSuperAdmin)
                <a href="{{ route('admin.support-tickets.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Create Your First Ticket
                </a>
                @else
                <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-outline-secondary">Clear Filters</a>
                @endif
            </div>
            @endif
        </div>
        @if($tickets->hasPages())
        <div class="card-footer bg-white border-0 pt-0 pb-4 px-4">
            {{ $tickets->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
