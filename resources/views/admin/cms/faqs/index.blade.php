@extends('layouts.app')

@section('title', 'Manage FAQs')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">FAQs</h4>
            <p class="text-muted small mb-0">Manage frequently asked questions shown on the website.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#faqModal" onclick="resetFaqForm()">
            <i class="bi bi-plus-lg me-1"></i>Add FAQ
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-1">FAQ Directory</h5>
            <p class="text-muted small mb-0">Edit answer quality, control ordering, and toggle visibility quickly.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="faqsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Category</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faqs as $faq)
                        <tr id="faq-row-{{ $faq->id }}">
                            <td>{{ $faq->sort_order }}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 350px;" title="{{ $faq->question }}">
                                    {{ $faq->question }}
                                </div>
                            </td>
                            <td>
                                @if($faq->category)
                                <span class="badge bg-light text-dark">{{ $faq->category }}</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $faq->sort_order }}</td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox" 
                                           data-url="{{ route('admin.cms.faqs.toggle', $faq->id) }}"
                                           {{ $faq->is_active ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1 edit-faq" 
                                        data-id="{{ $faq->id }}" data-question="{{ $faq->question }}" 
                                        data-answer="{{ $faq->answer }}" data-category="{{ $faq->category }}"
                                        data-sort="{{ $faq->sort_order }}" data-active="{{ $faq->is_active }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-record" 
                                        data-url="{{ route('admin.cms.faqs.destroy', $faq->id) }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Modal -->
<div class="modal fade" id="faqModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add FAQ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="faqForm">
                <div class="modal-body">
                    <input type="hidden" id="faqId">
                    <input type="hidden" id="formMethod" value="POST">
                    
                    <div class="mb-3">
                        <label for="question" class="form-label fw-semibold">Question <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="question" name="question" required>
                    </div>
                    <div class="mb-3">
                        <label for="answer" class="form-label fw-semibold">Answer <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="answer" name="answer" rows="5" required></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="category" class="form-label fw-semibold">Category</label>
                            <input type="text" class="form-control" id="category" name="category" placeholder="e.g. General, Billing, Technical">
                        </div>
                        <div class="col-md-3">
                            <label for="sort_order" class="form-label fw-semibold">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label fw-semibold" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveFaqBtn">
                        <span class="btn-text">Save</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
let currentEditUrl = null;

function resetFaqForm() {
    $('#faqForm')[0].reset();
    $('#faqId').val('');
    $('#is_active').prop('checked', true);
    $('#formMethod').val('POST');
    currentEditUrl = '{{ route("admin.cms.faqs.store") }}';
    $('#modalTitle').text('Add FAQ');
}

$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' } });

    // Edit FAQ
    $('.edit-faq').on('click', function() {
        const btn = $(this);
        $('#faqId').val(btn.data('id'));
        $('#question').val(btn.data('question'));
        $('#answer').val(btn.data('answer'));
        $('#category').val(btn.data('category'));
        $('#sort_order').val(btn.data('sort'));
        $('#is_active').prop('checked', btn.data('active') == 1);
        $('#formMethod').val('PUT');
        currentEditUrl = '/admin/cms/faqs/' + btn.data('id');
        $('#modalTitle').text('Edit FAQ');
        new bootstrap.Modal('#faqModal').show();
    });

    // Save FAQ
    $('#faqForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#saveFaqBtn');
        const data = $(this).serialize() + ($('#formMethod').val() === 'PUT' ? '&_method=PUT&is_active=' + ($('#is_active').is(':checked') ? 1 : 0) : '&is_active=' + ($('#is_active').is(':checked') ? 1 : 0));

        btn.prop('disabled', true);
        btn.find('.btn-text').text('Saving...');
        btn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: currentEditUrl,
            method: 'POST',
            data: data,
            success: function(response) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function(xhr) {
                let msg = 'Failed to save.';
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                toastr.error(msg);
            },
            complete: function() {
                btn.prop('disabled', false);
                btn.find('.btn-text').text('Save');
                btn.find('.spinner-border').addClass('d-none');
            }
        });
    });

    // Toggle status
    $('.toggle-status').on('change', function() {
        const checkbox = $(this);
        $.post(checkbox.data('url'), function(response) {
            toastr.success(response.message);
        }).fail(function() {
            checkbox.prop('checked', !checkbox.prop('checked'));
        });
    });

    // Delete
    $('.delete-record').on('click', function() {
        if (!confirm('Are you sure you want to delete this FAQ?')) return;
        const url = $(this).data('url');
        $.ajax({
            url: url,
            method: 'DELETE',
            success: function(response) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function() { toastr.error('Failed to delete.'); }
        });
    });
});
</script>
@endpush
