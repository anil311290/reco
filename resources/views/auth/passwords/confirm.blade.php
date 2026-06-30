@extends('layouts.auth')
@section('title', 'Confirm Password')
@section('subtitle', 'Please confirm your password to continue')

@section('content')
    <p class="text-muted small text-center mb-4">
        This is a secure area of the application. Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('admin.password.confirm') }}">
        @csrf

        <div class="form-floating mb-4 position-relative">
            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                   id="password" name="password" placeholder="Password" required>
            <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
            <button type="button" class="password-toggle" onclick="togglePassword()">
                <i class="bi bi-eye" id="passwordToggleIcon"></i>
            </button>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-login">
            <i class="bi bi-check-circle me-2"></i>Confirm Password
        </button>

        @if(Route::has('password.request'))
        <div class="text-center mt-3">
            <a href="{{ route('admin.password.request') }}" class="text-decoration-none small fw-medium" style="color: #7367f0;">
                Forgot password?
            </a>
        </div>
        @endif
    </form>
@endsection

@push('scripts')
<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('passwordToggleIcon');
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
