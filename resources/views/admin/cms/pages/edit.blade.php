@extends('layouts.app')

@section('title', 'Edit Page - ' . $page->title)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit Page: {{ $page->title }}</h1>
            <p class="text-muted small mb-0">/{{ $page->slug }}</p>
        </div>
        <a href="{{ route('admin.cms.pages.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Pages
        </a>
    </div>

    <form id="pageForm" action="{{ route('admin.cms.pages.update', $page->slug) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Page Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="{{ $page->title }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="pageContent" class="form-label fw-semibold">Content</label>
                            <textarea class="form-control" id="pageContent" name="content" rows="20">{{ $page->content }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-semibold">Page Settings</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold small">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" {{ $page->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $page->status === 'published' ? 'selected' : '' }}>Published</option>
                                <option value="archived" {{ $page->status === 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="template" class="form-label fw-semibold small">Template</label>
                            <select class="form-select" id="template" name="template">
                                <option value="default" {{ $page->template === 'default' ? 'selected' : '' }}>Default</option>
                                <option value="landing" {{ $page->template === 'landing' ? 'selected' : '' }}>Landing</option>
                                <option value="pricing" {{ $page->template === 'pricing' ? 'selected' : '' }}>Pricing</option>
                                <option value="faq" {{ $page->template === 'faq' ? 'selected' : '' }}>FAQ</option>
                            </select>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="show_in_nav" name="show_in_nav" value="1" {{ $page->show_in_nav ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="show_in_nav">Show in Navigation</label>
                        </div>

                        <div class="mb-0">
                            <label for="nav_order" class="form-label fw-semibold small">Nav Order</label>
                            <input type="number" class="form-control" id="nav_order" name="nav_order" value="{{ $page->nav_order }}" min="0">
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-semibold">SEO</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="meta_title" class="form-label fw-semibold small">Meta Title</label>
                            <input type="text" class="form-control" id="meta_title" name="meta_title" value="{{ $page->meta_title }}">
                        </div>
                        <div class="mb-0">
                            <label for="meta_description" class="form-label fw-semibold small">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="3">{{ $page->meta_description }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <span class="btn-text"><i class="bi bi-check-lg me-1"></i>Save Page</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('styles')
<link href="{{ asset('assets/vendor/summernote/summernote-bs5.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('assets/vendor/summernote/summernote-bs5.min.js') }}"></script>
<script>
$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' } });

    $('#pageContent').summernote({
        height: 400,
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['fontsize', 'fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });

    $('#pageForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const btn = $('#saveBtn');

        btn.prop('disabled', true);
        btn.find('.btn-text').text('Saving...');
        btn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                toastr.success(response.message);
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
                btn.find('.btn-text').html('<i class="bi bi-check-lg me-1"></i>Save Page');
                btn.find('.spinner-border').addClass('d-none');
            }
        });
    });
});
</script>
@endpush
