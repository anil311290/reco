@extends('layouts.app')

@section('title', 'CMS Pages')

@section('content')
<div class="container-fluid px-0">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="mb-0">CMS Pages</h4>
            <p class="text-muted small mb-0">Manage website page content and navigation visibility.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-1">Page Directory</h5>
            <p class="text-muted small mb-0">Publish, archive, and control nav placement for public website pages.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Template</th>
                            <th>Status</th>
                            <th>In Nav</th>
                            <th>Nav Order</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pages as $page)
                        <tr>
                            <td>
                                <div>
                                    <h6 class="mb-0 fw-semibold">{{ $page->title }}</h6>
                                    <small class="text-muted">/{{ $page->slug }}</small>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $page->template }}</span></td>
                            <td>
                                @if($page->status === 'published')
                                <span class="badge bg-success">Published</span>
                                @elseif($page->status === 'draft')
                                <span class="badge bg-warning text-dark">Draft</span>
                                @else
                                <span class="badge bg-secondary">Archived</span>
                                @endif
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-nav" type="checkbox" 
                                           data-url="{{ route('admin.cms.pages.toggle-nav', $page->slug) }}"
                                           {{ $page->show_in_nav ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>{{ $page->nav_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.cms.pages.edit', $page->slug) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </a>
                                @if($page->status === 'published')
                                @php
                                    $routeMap = [
                                        'home' => 'website.home',
                                        'features' => 'website.features',
                                        'pricing' => 'website.pricing',
                                        'faq' => 'website.faq',
                                        'about' => 'website.about',
                                        'contact' => 'website.contact',
                                        'privacy-policy' => 'website.privacy',
                                        'terms' => 'website.terms',
                                    ];
                                    $previewRoute = $routeMap[$page->slug] ?? null;
                                @endphp
                                @if($previewRoute)
                                <a href="{{ route($previewRoute) }}" 
                                   target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' } });

    $('.toggle-nav').on('change', function() {
        const checkbox = $(this);
        $.post(checkbox.data('url'), function(response) {
            toastr.success(response.message);
        }).fail(function() {
            checkbox.prop('checked', !checkbox.prop('checked'));
            toastr.error('Failed to update.');
        });
    });
});
</script>
@endpush
