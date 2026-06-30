@extends('layouts.auth')
@section('title', 'Create Account')
@section('subtitle', 'Register for a new Reco account')

@section('content')
    <form method="POST" action="{{ route('register.submit') }}">
        @csrf
        <input type="hidden" name="plan_slug" value="{{ request('plan', 'trial') }}">

        <div class="form-floating mb-3">
            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                   id="name" name="name" placeholder="Full Name"
                   value="{{ old('name') }}" required autofocus>
            <label for="name"><i class="bi bi-person me-1"></i> Full Name</label>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-3">
            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                   id="email" name="email" placeholder="name@example.com"
                   value="{{ old('email') }}" required>
            <label for="email"><i class="bi bi-envelope me-1"></i> Email address</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-3">
            <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                   id="company_name" name="company_name" placeholder="Company Name"
                   value="{{ old('company_name') }}" required>
            <label for="company_name"><i class="bi bi-building me-1"></i> Company Name</label>
            @error('company_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-3">
            <input type="email" class="form-control @error('company_email') is-invalid @enderror" 
                   id="company_email" name="company_email" placeholder="company@example.com"
                   value="{{ old('company_email') }}">
            <label for="company_email"><i class="bi bi-envelope-paper me-1"></i> Company Email</label>
            @error('company_email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-3 position-relative">
            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                   id="password" name="password" placeholder="Password" required>
            <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
            <button type="button" class="password-toggle" onclick="togglePassword('password', 'passwordToggleIcon')">
                <i class="bi bi-eye" id="passwordToggleIcon"></i>
            </button>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-4 position-relative">
            <input type="password" class="form-control" 
                   id="password-confirm" name="password_confirmation" placeholder="Confirm Password" required>
            <label for="password-confirm"><i class="bi bi-lock-fill me-1"></i> Confirm Password</label>
            <button type="button" class="password-toggle" onclick="togglePassword('password-confirm', 'confirmToggleIcon')">
                <i class="bi bi-eye" id="confirmToggleIcon"></i>
            </button>
        </div>

        <div class="alert alert-info small mb-3">
            <i class="bi bi-info-circle me-2"></i>
            After registration, admin approval is required before you can access your account.
        </div>

        <button type="submit" class="btn btn-login">
            <i class="bi bi-person-plus me-2"></i>Register
        </button>

        <div class="text-center mt-3">
            <span class="text-muted small">Already have an account? </span>
            <a href="{{ route('admin.login') }}" class="text-decoration-none small fw-medium" style="color: #6366f1;">Sign In</a>
        </div>
    </form>
@endsection

@push('scripts')
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
</script>
@endpush
