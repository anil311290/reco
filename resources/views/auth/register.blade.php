@extends('layouts.auth')
@section('title', 'Create Account')
@section('subtitle', 'Register for a new Reco account')

@section('content')
    <form id="registerForm" method="POST" action="{{ route('register.submit') }}">
        @csrf

        <div class="form-floating mb-3">
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                placeholder="Full Name" value="{{ old('name') }}" required autofocus>
            <label for="name"><i class="bi bi-person me-1"></i> Full Name</label>
            <div class="invalid-feedback" id="name-error"></div>
        </div>

        <div class="form-floating mb-3">
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                placeholder="name@example.com" value="{{ old('email') }}" required>
            <label for="email"><i class="bi bi-envelope me-1"></i> Email address</label>
            <div class="invalid-feedback" id="email-error"></div>
        </div>

        <div class="form-floating mb-3">
            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                placeholder="Mobile Number" value="{{ old('phone') }}" pattern="[0-9]{10}" maxlength="10" inputmode="numeric" required>
            <label for="phone"><i class="bi bi-phone me-1"></i> Mobile Number</label>
            <div class="invalid-feedback" id="phone-error"></div>
        </div>

        <div class="form-floating mb-3">
            <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name"
                name="company_name" placeholder="Company Name" value="{{ old('company_name') }}" required>
            <label for="company_name"><i class="bi bi-building me-1"></i> Company Name</label>
            <div class="invalid-feedback" id="company_name-error"></div>
        </div>

        <div class="form-floating mb-3">
            <select class="form-select @error('plan_slug') is-invalid @enderror" id="plan_slug" name="plan_slug"
                required>
                <option value="" disabled @selected(!old('plan_slug', request('plan')))>Select a plan</option>
                @foreach ($plans as $plan)
                    @php $planPrice = (float) $plan->monthly_price; @endphp
                    <option value="{{ $plan->slug }}" data-price="{{ $planPrice }}" @selected(old('plan_slug', request('plan')) === $plan->slug)>
                        {{ $plan->name }} —
                        @if ($planPrice <= 0)
                            Free
                        @else
                            ₹{{ number_format($planPrice, 0) }}/month
                        @endif
                    </option>
                @endforeach
            </select>
            <label for="plan_slug"><i class="bi bi-card-checklist me-1"></i> Subscription Plan</label>
            <div class="invalid-feedback" id="plan_slug-error"></div>
        </div>

        <div class="form-floating mb-3 position-relative">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                name="password" placeholder="Password" required>
            <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
            <button type="button" class="password-toggle" onclick="togglePassword('password', 'passwordToggleIcon')">
                <i class="bi bi-eye" id="passwordToggleIcon"></i>
            </button>
            <div class="invalid-feedback" id="password-error"></div>
        </div>

        <div class="form-floating mb-4 position-relative">
            <input type="password" class="form-control" id="password-confirm" name="password_confirmation"
                placeholder="Confirm Password" required>
            <label for="password-confirm"><i class="bi bi-lock-fill me-1"></i> Confirm Password</label>
            <button type="button" class="password-toggle"
                onclick="togglePassword('password-confirm', 'confirmToggleIcon')">
                <i class="bi bi-eye" id="confirmToggleIcon"></i>
            </button>
        </div>

        <div class="alert alert-info small mb-3" id="planInfo">
            <i class="bi bi-info-circle me-2"></i>
            After registration, admin approval is required before you can access your account.
        </div>

        <button type="submit" class="btn btn-login" id="registerBtn">
            <i class="bi bi-person-plus me-2"></i>Register
        </button>

        <div class="text-center mt-3">
            <span class="text-muted small">Already have an account? </span>
            <a href="{{ route('admin.login') }}" class="text-decoration-none small fw-medium" style="color: #6366f1;">Sign
                In</a>
        </div>
    </form>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/toastr/toastr.min.js') }}"></script>
<link href="{{ asset('assets/vendor/toastr/toastr.min.css') }}" rel="stylesheet">
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    function openRazorpayCheckout(checkout) {
        const options = {
            key: checkout.key_id,
            amount: checkout.amount_paise,
            currency: checkout.currency || 'INR',
            name: '{{ config('app.name', 'Reco') }}',
            description: checkout.description,
            order_id: checkout.order_id,
            prefill: {
                name: checkout.user_name || '',
                email: checkout.user_email || '',
                contact: checkout.user_phone || ''
            },
            theme: { color: '#6366f1' },
            handler: function (response) {
                $.ajax({
                    url: '{{ route('register.verify-payment') }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_signature: response.razorpay_signature
                    },
                    success: function (r) {
                        toastr.success(r.message || 'Payment successful!');
                        setTimeout(function () {
                            window.location.href = '{{ route('admin.login') }}';
                        }, 1500);
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Payment verification failed');
                    }
                });
            },
            modal: {
                ondismiss: function () {
                    toastr.info('Payment cancelled. Your account is pending payment.');
                }
            }
        };

        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response) {
            toastr.error(response.error?.description || 'Payment failed');
        });
        rzp.open();
    }

    $(document).ready(function () {
        // Update plan info message based on selected plan
        $('#plan_slug').on('change', function () {
            const selected = $(this).find('option:selected');
            const price = parseFloat(selected.data('price')) || 0;
            const $info = $('#planInfo');

            if (price > 0) {
                $info.html(
                    '<i class="bi bi-credit-card me-2"></i>' +
                    'You selected a paid plan (₹' + price.toLocaleString('en-IN') + '/month). ' +
                    'After registration, you\'ll be prompted to complete payment via Razorpay.'
                );
            } else {
                $info.html(
                    '<i class="bi bi-info-circle me-2"></i>' +
                    'After registration, admin approval is required before you can access your account.'
                );
            }
        });

        // AJAX form submission
        $('#registerForm').on('submit', function (e) {
            e.preventDefault();

            const $btn = $('#registerBtn');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Processing...');

            // Clear previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    if (response.requires_payment && response.checkout) {
                        // Open Razorpay checkout popup
                        openRazorpayCheckout(response.checkout);
                        $btn.prop('disabled', false).html('<i class="bi bi-person-plus me-2"></i>Register');
                    } else {
                        // Free/trial plan — success
                        toastr.success(response.message || 'Account created successfully!');
                        setTimeout(function () {
                            window.location.href = '{{ route('admin.login') }}';
                        }, 1500);
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="bi bi-person-plus me-2"></i>Register');

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function (field) {
                            const $input = $('#' + field);
                            $input.addClass('is-invalid');
                            $('#' + field + '-error').text(errors[field][0]);
                        });
                        toastr.error('Please correct the errors and try again.');
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Registration failed. Please try again.');
                    }
                }
            });
        });
    });
</script>
@endpush
