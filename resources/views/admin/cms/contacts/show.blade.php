@extends('layouts.app')

@section('title', 'Contact Submission')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Contact Submission #{{ $contact->id }}</h1>
            <p class="text-muted small mb-0">Received {{ $contact->created_at->diffForHumans() }}</p>
        </div>
        <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                            <span class="fw-bold text-primary">{{ strtoupper(substr($contact->name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-semibold">{{ $contact->name }}</h5>
                            <small class="text-muted">{{ $contact->email }}{{ $contact->phone ? ' · ' . $contact->phone : '' }}</small>
                        </div>
                        <div class="ms-auto">
                            @php
                            $classes = ['new' => 'bg-primary', 'read' => 'bg-info', 'replied' => 'bg-success', 'archived' => 'bg-secondary'];
                            @endphp
                            <span class="badge {{ $classes[$contact->status] ?? 'bg-secondary' }} px-3 py-2">
                                {{ ucfirst($contact->status) }}
                            </span>
                        </div>
                    </div>

                    @if($contact->subject)
                    <h6 class="fw-semibold mb-2">Subject</h6>
                    <p class="text-muted mb-4">{{ $contact->subject }}</p>
                    @endif

                    <h6 class="fw-semibold mb-2">Message</h6>
                    <div class="bg-light rounded-3 p-4">
                        <p class="mb-0" style="white-space: pre-wrap;">{{ $contact->message }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-semibold">Update Status</h6>
                </div>
                <div class="card-body p-4">
                    <form id="updateForm" action="{{ route('admin.contacts.update', $contact->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <select class="form-select" name="status">
                                <option value="read" {{ $contact->status === 'read' ? 'selected' : '' }}>Read</option>
                                <option value="replied" {{ $contact->status === 'replied' ? 'selected' : '' }}>Replied</option>
                                <option value="archived" {{ $contact->status === 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Admin Notes</label>
                            <textarea class="form-control" name="admin_notes" rows="3" placeholder="Internal notes...">{{ $contact->admin_notes }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="updateBtn">
                            <span class="btn-text">Update</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-semibold">Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="small">
                        <p class="mb-2"><strong>Submitted:</strong> {{ $contact->created_at->format('d-M-Y h:i A') }}</p>
                        <p class="mb-2"><strong>IP:</strong> {{ $contact->created_by_ip ?? '-' }}</p>
                        @if($contact->read_at)
                        <p class="mb-2"><strong>Read At:</strong> {{ $contact->read_at->format('d-M-Y h:i A') }}</p>
                        @endif
                        @if($contact->replied_at)
                        <p class="mb-0"><strong>Replied:</strong> {{ $contact->replied_at->format('d-M-Y h:i A') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' } });

    $('#updateForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#updateBtn');
        btn.prop('disabled', true);
        btn.find('.btn-text').text('Updating...');
        btn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize() + '&_method=PUT',
            success: function(r) { toastr.success(r.message); setTimeout(() => location.reload(), 800); },
            error: function() { toastr.error('Failed to update.'); },
            complete: function() { btn.prop('disabled', false); btn.find('.btn-text').text('Update'); btn.find('.spinner-border').addClass('d-none'); }
        });
    });
});
</script>
@endpush
