@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Create New Role</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Roles
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form id="roleForm" method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="{{ old('name') }}" required placeholder="e.g., Administrator">
                    <div class="invalid-feedback" id="name-error"></div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="slug" name="slug" 
                           value="{{ old('slug') }}" required placeholder="e.g., administrator">
                    <div class="invalid-feedback" id="slug-error"></div>
                    <small class="text-muted">Lowercase letters, numbers, and hyphens only</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" 
                              placeholder="Enter role description">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1"
                               {{ old('is_default') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_default">Set as Default Role</label>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <hr>

            <h5 class="mb-3">Permissions</h5>
            
            @foreach($permissions as $module => $modulePermissions)
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="form-check">
                            <input class="form-check-input module-checkbox" type="checkbox" 
                                   id="module_{{ Str::slug($module) }}" data-module="{{ Str::slug($module) }}">
                            <label class="form-check-label fw-bold" for="module_{{ Str::slug($module) }}">
                                {{ $module ?? 'General' }}
                            </label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($modulePermissions as $permission)
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input permission-checkbox" 
                                               type="checkbox" 
                                               name="permissions[]" 
                                               value="{{ $permission->id }}"
                                               id="permission_{{ $permission->id }}"
                                               data-module="{{ Str::slug($module) }}">
                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                            {{ $permission->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Create Role
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-generate slug from name
    $('#name').on('input', function() {
        const slug = $(this).val()
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        $('#slug').val(slug);
    });

    // Module checkbox - select/deselect all permissions in module
    $('.module-checkbox').on('change', function() {
        const module = $(this).data('module');
        const isChecked = $(this).prop('checked');
        $(`.permission-checkbox[data-module="${module}"]`).prop('checked', isChecked);
    });

    // Permission checkbox - update module checkbox state
    $('.permission-checkbox').on('change', function() {
        const module = $(this).data('module');
        const total = $(`.permission-checkbox[data-module="${module}"]`).length;
        const checked = $(`.permission-checkbox[data-module="${module}"]:checked`).length;
        $(`#module_${module}`).prop('checked', total === checked);
    });

    // Form submission
    ajaxFormSubmit('roleForm', '{{ route("admin.roles.store") }}', 'POST', function(response) {
        window.location.href = '{{ route("admin.roles.index") }}';
    });
});
</script>
@endpush
