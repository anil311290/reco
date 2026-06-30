@extends('layouts.app')

@section('title', 'Contact Submissions')

@section('content')
<div class="container-fluid px-0">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="mb-0">Contact Submissions</h4>
            <p class="text-muted small mb-0">Review customer inquiries submitted from the website contact form.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-1">Inquiry Directory</h5>
            <p class="text-muted small mb-0">Mark submissions as read and open full message details from actions.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="contactsTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    loadDatatable('contactsTable', '{{ route("admin.contacts.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'name', name: 'name' },
        { data: 'email', name: 'email' },
        { data: 'subject', name: 'subject', defaultContent: '-' },
        { data: 'status_badge', name: 'status', orderable: false, searchable: false },
        { data: 'created_at', name: 'created_at', render: function(data) {
            if (!data) {
                return '-';
            }

            const parsed = new Date(data);
            if (isNaN(parsed.getTime())) {
                return data;
            }

            return parsed.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }},
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
    ], {
        order: [[5, 'desc']]
    });
});
</script>
@endpush
