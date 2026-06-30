@extends('layouts.app')

@section('title', 'Theme Settings')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Theme Settings</h4>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Customize Theme</h5>
            </div>
            <div class="card-body">
                <form id="themeForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Primary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" name="primary_color" value="{{ $currentTheme->primary_color ?? '#6366f1' }}">
                                <input type="text" class="form-control" name="primary_color_text" value="{{ $currentTheme->primary_color ?? '#6366f1' }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Secondary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" name="secondary_color" value="{{ $currentTheme->secondary_color ?? '#8b5cf6' }}">
                                <input type="text" class="form-control" name="secondary_color_text" value="{{ $currentTheme->secondary_color ?? '#8b5cf6' }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Accent Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" name="accent_color" value="{{ $currentTheme->accent_color ?? '#06b6d4' }}">
                                <input type="text" class="form-control" name="accent_color_text" value="{{ $currentTheme->accent_color ?? '#06b6d4' }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sidebar Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" name="sidebar_color" value="{{ $currentTheme->sidebar_color ?? '#1e1b4b' }}">
                                <input type="text" class="form-control" name="sidebar_color_text" value="{{ $currentTheme->sidebar_color ?? '#1e1b4b' }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Header Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" name="header_color" value="{{ $currentTheme->header_color ?? '#ffffff' }}">
                                <input type="text" class="form-control" name="header_color_text" value="{{ $currentTheme->header_color ?? '#ffffff' }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Text Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" name="text_color" value="{{ $currentTheme->text_color ?? '#1f2937' }}">
                                <input type="text" class="form-control" name="text_color_text" value="{{ $currentTheme->text_color ?? '#1f2937' }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Background Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" name="bg_color" value="{{ $currentTheme->bg_color ?? '#f9fafb' }}">
                                <input type="text" class="form-control" name="bg_color_text" value="{{ $currentTheme->bg_color ?? '#f9fafb' }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Font Family</label>
                            <select class="form-select" name="font_family">
                                <option value="Inter" {{ ($currentTheme->font_family ?? '') == 'Inter' ? 'selected' : '' }}>Inter</option>
                                <option value="Poppins" {{ ($currentTheme->font_family ?? '') == 'Poppins' ? 'selected' : '' }}>Poppins</option>
                                <option value="Roboto" {{ ($currentTheme->font_family ?? '') == 'Roboto' ? 'selected' : '' }}>Roboto</option>
                                <option value="Open Sans" {{ ($currentTheme->font_family ?? '') == 'Open Sans' ? 'selected' : '' }}>Open Sans</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dark Mode</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="dark_mode" value="1" {{ ($currentTheme->dark_mode ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label">Enable Dark Mode</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Save Theme</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Predefined Themes</h5>
            </div>
            <div class="card-body">
                @foreach($themes as $theme)
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                    <div class="d-flex align-items-center">
                        <div class="me-3" style="width:30px;height:30px;border-radius:50%;background:{{ $theme->primary_color }}"></div>
                        <div>
                            <strong>{{ $theme->name }}</strong>
                            @if($theme->is_default)
                            <span class="badge bg-info ms-1">Default</span>
                            @endif
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary apply-theme-btn" data-id="{{ $theme->id }}">Apply</button>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$('input[type="color"]').on('input', function() {
    $(this).closest('.input-group').find('input[type="text"]').val($(this).val());
});

$('input[type="text"]').on('change', function() {
    $(this).closest('.input-group').find('input[type="color"]').val($(this).val());
});

$('#themeForm').on('submit', function(e) {
    e.preventDefault();
    let data = {};
    $(this).serializeArray().forEach(function(item) {
        if (!item.name.endsWith('_text')) {
            data[item.name] = item.value;
        }
    });
    data.dark_mode = $('input[name="dark_mode"]').is(':checked') ? 1 : 0;

    $.ajax({
        url: '{{ route("admin.themes.update") }}',
        type: 'PUT',
        data: data,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(r) { toastr.success(r.message); },
        error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Error'); }
    });
});

$(document).on('click', '.apply-theme-btn', function() {
    $.ajax({
        url: '{{ route("admin.themes.apply") }}',
        type: 'POST',
        data: { theme_id: $(this).data('id') },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(r) { toastr.success(r.message); setTimeout(() => location.reload(), 1000); },
        error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Error'); }
    });
});
</script>
@endsection
