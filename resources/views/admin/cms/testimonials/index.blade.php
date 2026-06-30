@extends('layouts.app')

@section('title', 'Manage Testimonials')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Testimonials</h4>
            <p class="text-muted small mb-0">Manage customer testimonials and featured highlights for the website.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testimonialModal" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i>Add Testimonial
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-1">Testimonial Directory</h5>
            <p class="text-muted small mb-0">Maintain testimonial quality, rating, and activation status in one place.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Company</th>
                            <th>Rating</th>
                            <th>Featured</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($testimonials as $t)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width:36px;height:36px;">
                                        <span class="fw-bold text-primary small">{{ strtoupper(substr($t->client_name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-semibold small">{{ $t->client_name }}</h6>
                                        @if($t->designation)<small class="text-muted">{{ $t->designation }}</small>@endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $t->company_name ?? '-' }}</td>
                            <td>
                                @for($i = 0; $i < $t->rating; $i++)
                                <i class="bi bi-star-fill text-warning small"></i>
                                @endfor
                            </td>
                            <td>
                                @if($t->is_featured)
                                <span class="badge bg-primary">Featured</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox" 
                                           data-url="{{ route('admin.cms.testimonials.toggle', $t->id) }}"
                                           {{ $t->is_active ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1 edit-testimonial" 
                                        data-id="{{ $t->id }}" data-name="{{ $t->client_name }}" 
                                        data-company="{{ $t->company_name }}" data-designation="{{ $t->designation }}"
                                        data-text="{{ $t->testimonial }}" data-rating="{{ $t->rating }}"
                                        data-featured="{{ $t->is_featured }}" data-active="{{ $t->is_active }}"
                                        data-order="{{ $t->sort_order }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-testimonial" 
                                        data-url="{{ route('admin.cms.testimonials.destroy', $t->id) }}">
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

<!-- Testimonial Modal -->
<div class="modal fade" id="testimonialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Testimonial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="testimonialForm">
                <div class="modal-body">
                    <input type="hidden" id="testimonialId">
                    <input type="hidden" id="formMethod" value="POST">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Client Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="client_name" name="client_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company</label>
                            <input type="text" class="form-control" id="company_name" name="company_name">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Designation</label>
                            <input type="text" class="form-control" id="designation" name="designation">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Rating</label>
                            <select class="form-select" id="rating" name="rating">
                                @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Testimonial <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="testimonial_text" name="testimonial" rows="4" required></textarea>
                    </div>
                    <div class="d-flex gap-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1">
                            <label class="form-check-label fw-semibold" for="is_featured">Featured</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label fw-semibold" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
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

function resetForm() {
    $('#testimonialForm')[0].reset();
    $('#testimonialId').val('');
    $('#is_active').prop('checked', true);
    $('#is_featured').prop('checked', false);
    $('#formMethod').val('POST');
    currentEditUrl = '{{ route("admin.cms.testimonials.store") }}';
    $('#modalTitle').text('Add Testimonial');
}

$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' } });

    $('.edit-testimonial').on('click', function() {
        const b = $(this);
        $('#testimonialId').val(b.data('id'));
        $('#client_name').val(b.data('name'));
        $('#company_name').val(b.data('company'));
        $('#designation').val(b.data('designation'));
        $('#testimonial_text').val(b.data('text'));
        $('#rating').val(b.data('rating'));
        $('#sort_order').val(b.data('order'));
        $('#is_featured').prop('checked', b.data('featured') == 1);
        $('#is_active').prop('checked', b.data('active') == 1);
        $('#formMethod').val('PUT');
        currentEditUrl = '/admin/cms/testimonials/' + b.data('id');
        $('#modalTitle').text('Edit Testimonial');
        new bootstrap.Modal('#testimonialModal').show();
    });

    $('#testimonialForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#saveBtn');
        let data = $(this).serialize();
        data += '&is_active=' + ($('#is_active').is(':checked') ? 1 : 0);
        data += '&is_featured=' + ($('#is_featured').is(':checked') ? 1 : 0);
        if ($('#formMethod').val() === 'PUT') data += '&_method=PUT';

        btn.prop('disabled', true);
        btn.find('.btn-text').text('Saving...');
        btn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: currentEditUrl,
            method: 'POST',
            data: data,
            success: function(r) { toastr.success(r.message); setTimeout(() => location.reload(), 800); },
            error: function(xhr) {
                let msg = 'Failed to save.';
                if (xhr.status === 422 && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                toastr.error(msg);
            },
            complete: function() { btn.prop('disabled', false); btn.find('.btn-text').text('Save'); btn.find('.spinner-border').addClass('d-none'); }
        });
    });

    $('.toggle-status').on('change', function() {
        const c = $(this);
        $.post(c.data('url'), function(r) { toastr.success(r.message); }).fail(function() { c.prop('checked', !c.prop('checked')); });
    });

    $('.delete-testimonial').on('click', function() {
        if (!confirm('Delete this testimonial?')) return;
        $.ajax({ url: $(this).data('url'), method: 'DELETE',
            success: function(r) { toastr.success(r.message); setTimeout(() => location.reload(), 800); },
            error: function() { toastr.error('Failed to delete.'); }
        });
    });
});
</script>
@endpush
