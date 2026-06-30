@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Settings</h4>
    </div>
</div>

<!-- Settings Tabs -->
<ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="company-tab" data-bs-toggle="tab" 
                data-bs-target="#company" type="button" role="tab">
            <i class="bi bi-building me-2"></i>Company Settings
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="subscription-tab" data-bs-toggle="tab" 
                data-bs-target="#subscription" type="button" role="tab">
            <i class="bi bi-credit-card me-2"></i>Subscription Plans
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="theme-tab" data-bs-toggle="tab" 
                data-bs-target="#theme" type="button" role="tab">
            <i class="bi bi-palette me-2"></i>Theme Settings
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="financial-tab" data-bs-toggle="tab" 
                data-bs-target="#financial" type="button" role="tab">
            <i class="bi bi-calendar3 me-2"></i>Financial Year
        </button>
    </li>
</ul>

<div class="tab-content" id="settingsTabContent">
    <!-- Company Settings Tab -->
    <div class="tab-pane fade show active" id="company" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Company Information</h5>
            </div>
            <div class="card-body">
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

    <!-- Theme Settings Tab -->
    <div class="tab-pane fade" id="theme" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Theme Customization</h5>
            </div>
            <div class="card-body">
                <form id="themeForm" method="POST" action="{{ route('admin.settings.theme') }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="primary_color" class="form-label">Primary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="primary_color_picker" 
                                   value="{{ $settings['theme']['primary_color'] ?? '#7367f0' }}">
                                <input type="text" class="form-control" id="primary_color" name="primary_color" 
                                       value="{{ $settings['theme']['primary_color'] ?? '#7367f0' }}">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label for="secondary_color" class="form-label">Secondary Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="secondary_color_picker" 
                                       value="{{ $settings['theme']['secondary_color'] ?? '#6b7280' }}">
                                <input type="text" class="form-control" id="secondary_color" name="secondary_color" 
                                       value="{{ $settings['theme']['secondary_color'] ?? '#6b7280' }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="sidebar_color" class="form-label">Sidebar Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="sidebar_color_picker" 
                                       value="{{ $settings['theme']['sidebar_color'] ?? '#ffffff' }}">
                                <input type="text" class="form-control" id="sidebar_color" name="sidebar_color" 
                                       value="{{ $settings['theme']['sidebar_color'] ?? '#ffffff' }}">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label for="header_color" class="form-label">Header Color</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="header_color_picker" 
                                       value="{{ $settings['theme']['header_color'] ?? '#ffffff' }}">
                                <input type="text" class="form-control" id="header_color" name="header_color" 
                                       value="{{ $settings['theme']['header_color'] ?? '#ffffff' }}">
                            </div>
                        </div>
                    </div>

                    <!-- Theme Preview -->
                    <div class="card bg-light mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Theme Preview</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex gap-3 mb-3">
                                <div class="rounded p-3" id="previewPrimary" style="background-color: #7367f0; color: white;">
                                    Primary
                                </div>
                                <div class="rounded p-3" id="previewSecondary" style="background-color: #a8aaae; color: white;">
                                    Secondary
                                </div>
                                <div class="rounded p-3" id="previewSidebar" style="background-color: #ffffff; color: #4b4b4b; border: 1px solid #e8e6f0;">
                                    Sidebar
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="previewBtn">
                                Preview Button
                            </button>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Save Theme Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Financial Year Tab -->
    <div class="tab-pane fade" id="financial" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Financial Year Management</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Financial year settings are managed in the Company Settings tab. 
                    The current financial year runs from {{ $company->financial_year_start ?? '04-01' }} to {{ $company->financial_year_end ?? '03-31' }}.
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>Current Financial Year</h6>
                                <p class="mb-1">
                                    <strong>Start:</strong> April 1, {{ date('Y') }}
                                </p>
                                <p class="mb-0">
                                    <strong>End:</strong> March 31, {{ date('Y') + 1 }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription Plans Tab -->
    <div class="tab-pane fade" id="subscription" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Subscription Plans Management</h5>
                @role('superadmin')
                <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-gear me-1"></i>Manage Plans
                </a>
                @endrole
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Manage subscription plans for your SaaS business. Companies can subscribe to these plans from their admin panel.
                </div>
                @role('superadmin')
                <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-grid me-2"></i>Go to Subscription Plans Management
                </a>
                @else
                <div class="alert alert-secondary mb-0">
                    Subscription plan catalog is managed by the superadmin. Company admins can review their active subscription from the subscription module.
                </div>
                @endrole
            </div>
        </div>
    </div>

    <!-- Accounting Settings Tab - Hidden -->
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Company form submission
    ajaxFormSubmit('companyForm', '{{ route("admin.settings.company") }}', 'PUT', function(response) {
        // Success message is already shown by ajaxFormSubmit
        // No need to show another toastr here
    });

    // Theme form submission
    ajaxFormSubmit('themeForm', '{{ route("admin.settings.theme") }}', 'PUT', function(response) {
        // Reload to apply new theme after a short delay
        setTimeout(function() {
            location.reload();
        }, 1500);
    });

    // Accounting Settings form removed
    });

    // Color picker sync
    function syncColorPicker(pickerId, inputId) {
        $(`#${pickerId}`).on('input', function() {
            $(`#${inputId}`).val($(this).val());
            updatePreview();
        });
        $(`#${inputId}`).on('input', function() {
            $(`#${pickerId}`).val($(this).val());
            updatePreview();
        });
    }

    syncColorPicker('primary_color_picker', 'primary_color');
    syncColorPicker('secondary_color_picker', 'secondary_color');
    syncColorPicker('sidebar_color_picker', 'sidebar_color');
    syncColorPicker('header_color_picker', 'header_color');

    // Update preview
    function updatePreview() {
        $('#previewPrimary').css('background-color', $('#primary_color').val());
        $('#previewSecondary').css('background-color', $('#secondary_color').val());
        $('#previewSidebar').css('background-color', $('#sidebar_color').val());
        $('#previewBtn').css({
            'background-color': $('#primary_color').val(),
            'border-color': $('#primary_color').val()
        });
    }

    // Initialize preview
    updatePreview();
});
</script>
@endpush
