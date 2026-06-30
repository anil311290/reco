@extends('layouts.auth')
@section('title', 'Set New Password')
@section('subtitle', 'Enter your new password below')

@section('content')
    <form method="POST" action="{{ route('admin.password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-floating mb-3">
            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                   id="email" name="email" placeholder="name@example.com"
                   value="{{ $email ?? old('email') }}" required autofocus>
            <label for="email"><i class="bi bi-envelope me-1"></i> Email address</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-floating mb-3 position-relative">
            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                   id="password" name="password" placeholder="New Password" required>
            <label for="password"><i class="bi bi-lock me-1"></i> New Password</label>
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

        <button type="submit" class="btn btn-login">
            <i class="bi bi-check-circle me-2"></i>Reset Password
        </button>
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
