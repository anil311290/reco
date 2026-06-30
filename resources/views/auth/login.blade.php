@extends('layouts.auth')
@section('title', 'Welcome Back')
@section('subtitle', 'Sign in to your Reco account')

@section('content')
    <style>
        .alert { animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .btn-success { transition: all 0.3s ease; }
    </style>

    <!-- JS Alert placeholders -->
    <div id="loginAlert" class="alert alert-danger d-none small" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i><span id="alertMessage"></span>
    </div>
    <div id="loginSuccess" class="alert d-none small text-center" role="alert" 
         style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <strong id="successMessage"></strong>
    </div>

    <form id="loginForm" method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        
        <!-- Email -->
        <div class="form-floating mb-3">
            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                   id="email" name="email" placeholder="name@example.com"
                   value="{{ old('email') }}" required autofocus>
            <label for="email"><i class="bi bi-envelope me-1"></i> Email address</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-floating mb-3 position-relative">
            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                   id="password" name="password" placeholder="Password"
                   value="" required>
            <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
            <button type="button" class="password-toggle" onclick="togglePassword()">
                <i class="bi bi-eye" id="passwordToggleIcon"></i>
            </button>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label small text-muted" for="remember">Remember me</label>
            </div>
            <a href="{{ route('admin.password.request') }}" class="text-decoration-none small fw-medium" style="color: #7367f0;">
                Forgot password?
            </a>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-login" id="loginBtn">
            <span class="btn-text">Sign In</span>
            <span class="spinner-border d-none" role="status">
                <span class="visually-hidden">Loading...</span>
            </span>
        </button>
    </form>
@endsection

@push('scripts')
<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const icon = document.getElementById('passwordToggleIcon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = $('#loginBtn');
            const btnText = submitBtn.find('.btn-text');
            const spinner = submitBtn.find('.spinner-border');
            const alertDiv = $('#loginAlert');
            const alertMessage = $('#alertMessage');
            const successDiv = $('#loginSuccess');
            const successMessage = $('#successMessage');
            
            alertDiv.addClass('d-none');
            successDiv.addClass('d-none');
            submitBtn.prop('disabled', true);
            btnText.text('Signing in...');
            spinner.removeClass('d-none');
            
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        // Show success alert
                        successMessage.text(response.message || 'Login successful! Redirecting...');
                        successDiv.removeClass('d-none').hide().fadeIn(300);
                        
                        // Update button
                        btnText.text('Success!');
                        spinner.addClass('d-none');
                        submitBtn.removeClass('btn-login').addClass('btn-success');
                        
                        // Redirect after delay
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    } else {
                        alertMessage.text(response.message);
                        alertDiv.removeClass('d-none').hide().fadeIn(300);
                        submitBtn.prop('disabled', false);
                        btnText.text('Sign In');
                        spinner.addClass('d-none');
                    }
                },
                error: function(xhr) {
                    let message = 'An error occurred. Please try again.';
                    if (xhr.status === 422 && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        if (errors.email) message = errors.email[0];
                        else if (errors.password) message = errors.password[0];
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    alertMessage.text(message);
                    alertDiv.removeClass('d-none');
                    submitBtn.prop('disabled', false);
                    btnText.text('Sign In');
                    spinner.addClass('d-none');
                }
            });
        });
    });
</script>
@endpush
