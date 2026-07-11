@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_number)

@section('content')
<div class="container-fluid px-0">
    <div class="row mb-3 align-items-center">
        <div class="col-md-8">
            <a href="{{ route('admin.support-tickets.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to tickets</a>
            <h4 class="mb-1 mt-2">{{ $ticket->subject }}</h4>
            <div class="text-muted small">
                {{ $ticket->ticket_number }}
                @if($isSuperAdmin && $ticket->company)
                · {{ $ticket->company->name }}
                @endif
                · Opened by {{ $ticket->user->name ?? 'User' }}
            </div>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $ticket->status)) }}</span>
            <span class="badge bg-secondary">{{ ucfirst($ticket->priority) }}</span>
        </div>
    </div>

    @if($isSuperAdmin)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="POST" action="{{ route('admin.support-tickets.status', $ticket->id) }}" class="row g-2 align-items-end">
                @csrf
                @method('PATCH')
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        @foreach(['open','in_progress','waiting_on_customer','resolved','closed'] as $status)
                        <option value="{{ $status }}" @selected($ticket->status === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Assign to</label>
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">Unassigned</option>
                        @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" @selected($ticket->assigned_to == $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">Update</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body support-chat" style="max-height: 480px; overflow-y: auto;">
            @foreach($messages as $message)
            @php
                $isMine = $message->user_id === auth()->id();
                $isStaff = $message->user && $message->user->isSuperAdmin();
            @endphp
            <div class="d-flex mb-3 {{ $isMine ? 'justify-content-end' : 'justify-content-start' }}">
                <div class="p-3 rounded {{ $isMine ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 75%;">
                    <div class="small fw-semibold mb-1">
                        {{ $message->user->name ?? 'User' }}
                        @if($message->is_internal)
                        <span class="badge bg-warning text-dark">Internal</span>
                        @elseif($isStaff)
                        <span class="badge bg-success">Support</span>
                        @endif
                    </div>
                    <div style="white-space: pre-wrap;">{{ $message->message }}</div>
                    <div class="small mt-2 opacity-75">@istDateTime($message->created_at)</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @if($ticket->isOpen() || $isSuperAdmin)
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.support-tickets.reply', $ticket->id) }}">
                @csrf
                <label class="form-label">Reply</label>
                <textarea name="message" rows="4" class="form-control mb-3" required placeholder="Type your message..."></textarea>
                @if($isSuperAdmin)
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_internal" value="1" id="isInternal">
                    <label class="form-check-label" for="isInternal">Internal note (visible to support staff only)</label>
                </div>
                @endif
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Send Message</button>
            </form>
        </div>
    </div>
    @else
    <div class="alert alert-secondary">This ticket is closed. Open a new ticket if you need further help.</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chat = document.querySelector('.support-chat');
    if (chat) chat.scrollTop = chat.scrollHeight;
});
</script>
@endpush
