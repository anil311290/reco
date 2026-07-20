@extends('layouts.app')

@section('title', 'Settings')

@section('content')
@php
    $theme = $settings['theme'] ?? [];
    $primary = $theme['primary_color'] ?? '#1f6feb';
    $secondary = $theme['secondary_color'] ?? '#6b7280';
    $sidebar = $theme['sidebar_color'] ?? '#ffffff';
    $header = $theme['header_color'] ?? '#ffffff';
@endphp

<style>
    .settings-page .settings-nav .nav-link {
        border: 1px solid var(--lp-border, #e8e6f0);
        border-radius: 10px;
        color: var(--lp-text, #6e6b7b);
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.65rem;
        background: #fff;
    }
    .settings-page .settings-nav .nav-link i {
        font-size: 1.1rem;
        width: 1.25rem;
        text-align: center;
    }
    .settings-page .settings-nav .nav-link.active {
        color: var(--lp-primary);
        border-color: var(--lp-primary);
        background: var(--lp-primary-50);
        font-weight: 600;
    }
    .settings-page .settings-nav .nav-link:hover:not(.active) {
        color: var(--lp-primary);
        background: var(--lp-primary-50);
    }
    .settings-page .color-field .form-control-color {
        width: 48px;
        height: 38px;
        padding: 0.2rem;
        cursor: pointer;
    }
    .settings-page .theme-swatch {
        height: 36px;
        min-height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 600;
        border: 1px solid rgba(0,0,0,0.08);
        letter-spacing: 0.01em;
    }
    .settings-page .theme-swatches {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .settings-page .theme-mini-preview {
        border: 1px solid var(--lp-border, #e8e6f0);
        border-radius: 12px;
        overflow: hidden;
        background: #f8f9fb;
    }
    .settings-page .theme-mini-preview .mini-header {
        height: 42px;
        display: flex;
        align-items: center;
        padding: 0 14px;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        font-size: 0.8rem;
        font-weight: 600;
    }
    .settings-page .theme-mini-preview .mini-body {
        display: flex;
        min-height: 140px;
    }
    .settings-page .theme-mini-preview .mini-sidebar {
        width: 88px;
        padding: 12px 8px;
        border-right: 1px solid rgba(0,0,0,0.06);
        font-size: 0.7rem;
    }
    .settings-page .theme-mini-preview .mini-sidebar .mini-nav {
        height: 10px;
        border-radius: 4px;
        margin-bottom: 8px;
        background: rgba(0,0,0,0.08);
    }
    .settings-page .theme-mini-preview .mini-sidebar .mini-nav.active {
        background: var(--lp-primary);
        opacity: 0.85;
    }
    .settings-page .theme-mini-preview .mini-content {
        flex: 1;
        padding: 14px;
    }
    .settings-page .theme-mini-preview .mini-card {
        background: #fff;
        border-radius: 8px;
        height: 70px;
        border: 1px solid rgba(0,0,0,0.06);
        margin-bottom: 10px;
    }
</style>

<div class="settings-page">
    <div class="row mb-4">
        <div class="col-md-8">
            <h4 class="mb-1">Settings</h4>
            <small class="text-muted">Manage company profile, theme, and related preferences.</small>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="nav flex-column settings-nav nav-pills" id="settingsTabs" role="tablist" aria-orientation="vertical">
                <button class="nav-link active" id="company-tab" data-bs-toggle="pill" data-bs-target="#company" type="button" role="tab">
                    <i class="bi bi-building"></i> Company
                </button>
                <button class="nav-link" id="theme-tab" data-bs-toggle="pill" data-bs-target="#theme" type="button" role="tab">
                    <i class="bi bi-palette"></i> Theme
                </button>
                <button class="nav-link" id="financial-tab" data-bs-toggle="pill" data-bs-target="#financial" type="button" role="tab">
                    <i class="bi bi-calendar3"></i> Financial Year
                </button>
                <button class="nav-link" id="subscription-tab" data-bs-toggle="pill" data-bs-target="#subscription" type="button" role="tab">
                    <i class="bi bi-credit-card"></i> Subscription
                </button>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="tab-content" id="settingsTabContent">
                <div class="tab-pane fade show active" id="company" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Company Information</h5>
                            <p class="text-muted small mb-4">Update your company profile used across invoices and reports.</p>
                            <form id="companyForm" method="POST" action="{{ route('admin.settings.company') }}">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="company_name" name="company_name"
                                               value="{{ old('company_name', $company->name ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="company_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="company_email" name="company_email"
                                               value="{{ old('company_email', $company->email ?? '') }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="company_phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="company_phone" name="company_phone"
                                               value="{{ old('company_phone', $company->phone ?? '') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="company_gst_number" class="form-label">GST Number</label>
                                        <input type="text" class="form-control" id="company_gst_number" name="company_gst_number"
                                               value="{{ old('company_gst_number', $company->gst_number ?? '') }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="company_pan_number" class="form-label">PAN Number</label>
                                        <input type="text" class="form-control" id="company_pan_number" name="company_pan_number"
                                               value="{{ old('company_pan_number', $company->pan_number ?? '') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="company_currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                        <select class="form-select" id="company_currency" name="company_currency" required>
                                            <option value="INR" {{ ($company->currency ?? 'INR') === 'INR' ? 'selected' : '' }}>INR - Indian Rupee</option>
                                            <option value="USD" {{ ($company->currency ?? '') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                            <option value="EUR" {{ ($company->currency ?? '') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                            <option value="GBP" {{ ($company->currency ?? '') === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="company_address" class="form-label">Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="company_address" name="company_address" rows="2" required>{{ old('company_address', $company->address ?? '') }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="company_city" class="form-label">City <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="company_city" name="company_city"
                                               value="{{ old('company_city', $company->city ?? '') }}" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="company_state" class="form-label">State <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="company_state" name="company_state"
                                               value="{{ old('company_state', $company->state ?? '') }}" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="company_postal_code" class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="company_postal_code" name="company_postal_code"
                                               value="{{ old('company_postal_code', $company->postal_code ?? '') }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="company_country" class="form-label">Country <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="company_country" name="company_country"
                                               value="{{ old('company_country', $company->country ?? 'India') }}" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="company_timezone" class="form-label">Timezone <span class="text-danger">*</span></label>
                                        <select class="form-select" id="company_timezone" name="company_timezone" required>
                                            <option value="Asia/Kolkata" {{ ($company->timezone ?? 'Asia/Kolkata') === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata (IST)</option>
                                            <option value="America/New_York" {{ ($company->timezone ?? '') === 'America/New_York' ? 'selected' : '' }}>America/New_York (EST)</option>
                                            <option value="Europe/London" {{ ($company->timezone ?? '') === 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="financial_year_start" class="form-label">FY Start <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="financial_year_start" name="financial_year_start"
                                               value="{{ old('financial_year_start', $company->financial_year_start ?? '04-01') }}"
                                               placeholder="MM-DD" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="financial_year_end" class="form-label">FY End <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="financial_year_end" name="financial_year_end"
                                               value="{{ old('financial_year_end', $company->financial_year_end ?? '03-31') }}"
                                               placeholder="MM-DD" required>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Save Company Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="theme" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
                                <div>
                                    <h5 class="card-title mb-1">Theme Customization</h5>
                                    <p class="text-muted small mb-0">Changes preview instantly. Click save to keep them permanently.</p>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="resetThemeBtn">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Saved
                                </button>
                            </div>

                            <form id="themeForm" method="POST" action="{{ route('admin.settings.theme') }}">
                                @csrf
                                @method('PUT')

                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <div class="row g-3 color-field">
                                            <div class="col-md-6">
                                                <label class="form-label" for="primary_color">Primary Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color theme-color-picker" data-target="primary_color" id="primary_color_picker" value="{{ $primary }}">
                                                    <input type="text" class="form-control theme-color-input" id="primary_color" name="primary_color" value="{{ $primary }}" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="secondary_color">Secondary Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color theme-color-picker" data-target="secondary_color" id="secondary_color_picker" value="{{ $secondary }}">
                                                    <input type="text" class="form-control theme-color-input" id="secondary_color" name="secondary_color" value="{{ $secondary }}" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="sidebar_color">Sidebar Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color theme-color-picker" data-target="sidebar_color" id="sidebar_color_picker" value="{{ $sidebar }}">
                                                    <input type="text" class="form-control theme-color-input" id="sidebar_color" name="sidebar_color" value="{{ $sidebar }}" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="header_color">Header Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color theme-color-picker" data-target="header_color" id="header_color_picker" value="{{ $header }}">
                                                    <input type="text" class="form-control theme-color-input" id="header_color" name="header_color" value="{{ $header }}" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="theme-swatches">
                                            <div class="theme-swatch" id="swatchPrimary" style="background: {{ $primary }}; color:#fff;">Primary</div>
                                            <div class="theme-swatch" id="swatchSecondary" style="background: {{ $secondary }}; color:#fff;">Secondary</div>
                                            <div class="theme-swatch" id="swatchSidebar" style="background: {{ $sidebar }}; color:#333;">Sidebar</div>
                                            <div class="theme-swatch" id="swatchHeader" style="background: {{ $header }}; color:#333;">Header</div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Live Preview</label>
                                        <div class="theme-mini-preview">
                                            <div class="mini-header" id="previewHeader" style="background: {{ $header }};">App Header</div>
                                            <div class="mini-body">
                                                <div class="mini-sidebar" id="previewSidebar" style="background: {{ $sidebar }};">
                                                    <div class="mini-nav active" id="previewNavActive"></div>
                                                    <div class="mini-nav"></div>
                                                    <div class="mini-nav"></div>
                                                </div>
                                                <div class="mini-content">
                                                    <div class="mini-card"></div>
                                                    <button type="button" class="btn btn-sm btn-primary" id="previewBtn">Primary Button</button>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-2">Sidebar, header, and primary buttons update as you change colors.</small>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary" id="saveThemeBtn">
                                        <i class="bi bi-check-circle me-2"></i>Save Theme Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="financial" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Financial Year</h5>
                            <p class="text-muted small mb-4">FY dates are configured under Company settings.</p>
                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle me-2"></i>
                                Current company FY window: {{ $company->financial_year_start ?? '04-01' }} to {{ $company->financial_year_end ?? '03-31' }}.
                            </div>
                            <a href="{{ route('admin.financial-years.index') }}" class="btn btn-outline-primary">
                                <i class="bi bi-calendar3 me-2"></i>Manage Financial Years
                            </a>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="subscription" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Subscription</h5>
                            <p class="text-muted small mb-4">Review plans and your company subscription.</p>
                            @role('superadmin')
                            <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-primary">
                                <i class="bi bi-grid me-2"></i>Manage Subscription Plans
                            </a>
                            @else
                            <div class="alert alert-secondary mb-0">
                                Subscription plan catalog is managed by the superadmin. Review your active plan from the subscription module.
                            </div>
                            @endrole
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const savedTheme = {
        primary_color: @json($primary),
        secondary_color: @json($secondary),
        sidebar_color: @json($sidebar),
        header_color: @json($header)
    };

    function normalizeHex(value) {
        if (!value) return null;
        value = String(value).trim();
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) return value.toLowerCase();
        if (/^[0-9A-Fa-f]{6}$/.test(value)) return ('#' + value).toLowerCase();
        return null;
    }

    function hexToRgb(hex) {
        const clean = hex.replace('#', '');
        return {
            r: parseInt(clean.substring(0, 2), 16),
            g: parseInt(clean.substring(2, 4), 16),
            b: parseInt(clean.substring(4, 6), 16)
        };
    }

    function adjustBrightness(hex, steps) {
        const rgb = hexToRgb(hex);
        const clamp = (n) => Math.max(0, Math.min(255, n + steps));
        const toHex = (n) => clamp(n).toString(16).padStart(2, '0');
        return '#' + toHex(rgb.r) + toHex(rgb.g) + toHex(rgb.b);
    }

    function rgba(hex, alpha) {
        const rgb = hexToRgb(hex);
        return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`;
    }

    function getThemeColors() {
        return {
            primary_color: normalizeHex($('#primary_color').val()) || savedTheme.primary_color,
            secondary_color: normalizeHex($('#secondary_color').val()) || savedTheme.secondary_color,
            sidebar_color: normalizeHex($('#sidebar_color').val()) || savedTheme.sidebar_color,
            header_color: normalizeHex($('#header_color').val()) || savedTheme.header_color
        };
    }

    function buildThemeCss(colors) {
        const primary = colors.primary_color;
        const secondary = colors.secondary_color;
        const sidebar = colors.sidebar_color;
        const header = colors.header_color;
        const hover = adjustBrightness(primary, -20);
        const rgb = hexToRgb(primary);

        return `
:root {
    --lp-primary: ${primary};
    --lp-primary-hover: ${hover};
    --lp-primary-light: ${rgba(primary, 0.1)};
    --lp-primary-50: ${rgba(primary, 0.08)};
    --lp-secondary: ${secondary};
    --lp-sidebar-bg: ${sidebar};
    --lp-sidebar-active: ${primary};
    --lp-sidebar-hover: ${rgba(primary, 0.06)};
    --lp-sidebar-active-bg: ${rgba(primary, 0.1)};
    --bs-primary: ${primary};
    --bs-primary-rgb: ${rgb.r}, ${rgb.g}, ${rgb.b};
}
header.header { background: ${header} !important; }
nav.sidebar { background: ${sidebar} !important; }
.btn-primary { background: ${primary}; border-color: ${primary}; }
.btn-primary:hover, .btn-primary:focus { background: ${hover}; border-color: ${hover}; }
`.trim();
    }

    function applyThemeCss(css) {
        let style = document.getElementById('dynamic-theme');
        if (!style) {
            style = document.createElement('style');
            style.id = 'dynamic-theme';
            document.head.appendChild(style);
        }
        style.textContent = css;
    }

    function updatePreviewCards(colors) {
        $('#swatchPrimary').css({ background: colors.primary_color, color: '#fff' });
        $('#swatchSecondary').css({ background: colors.secondary_color, color: '#fff' });
        $('#swatchSidebar').css({ background: colors.sidebar_color, color: '#333' });
        $('#swatchHeader').css({ background: colors.header_color, color: '#333' });
        $('#previewHeader').css('background', colors.header_color);
        $('#previewSidebar').css('background', colors.sidebar_color);
        $('#previewNavActive').css('background', colors.primary_color);
        $('#previewBtn').css({
            backgroundColor: colors.primary_color,
            borderColor: colors.primary_color
        });
    }

    function applyLiveTheme() {
        const colors = getThemeColors();
        applyThemeCss(buildThemeCss(colors));
        updatePreviewCards(colors);
    }

    function setThemeInputs(colors) {
        Object.keys(colors).forEach(function(key) {
            $('#' + key).val(colors[key]);
            $('#' + key + '_picker').val(colors[key]);
        });
    }

    $('.theme-color-picker').on('input change', function() {
        const target = $(this).data('target');
        $('#' + target).val($(this).val());
        applyLiveTheme();
    });

    $('.theme-color-input').on('input change', function() {
        const hex = normalizeHex($(this).val());
        if (!hex) return;
        $(this).val(hex);
        $('#' + this.id + '_picker').val(hex);
        applyLiveTheme();
    });

    $('#resetThemeBtn').on('click', function() {
        setThemeInputs(savedTheme);
        applyLiveTheme();
        toastr.info('Reverted to last saved theme');
    });

    ajaxFormSubmit('companyForm', '{{ route("admin.settings.company") }}', 'PUT');

    ajaxFormSubmit('themeForm', '{{ route("admin.settings.theme") }}', 'PUT', function(response) {
        const theme = response.data?.theme || getThemeColors();
        const css = response.data?.css || buildThemeCss(theme);

        Object.assign(savedTheme, theme);
        setThemeInputs(theme);
        applyThemeCss(css);
        updatePreviewCards(theme);
    });

    applyLiveTheme();
});
</script>
@endpush
