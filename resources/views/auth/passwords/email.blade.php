@extends('layouts.auth')
@section('title', 'Reset Password')
@section('subtitle', 'Enter your email to receive a password reset link')

@section('content')
    @if(session('status'))
    <div class="alert alert-success small" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('admin.password.email') }}">
        @csrf

        <div class="form-floating mb-4">
            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                   id="email" name="email" placeholder="name@example.com"
                   value="{{ old('email') }}" required autofocus>
            <label for="email"><i class="bi bi-envelope me-1"></i> Email address</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-login">
            <i class="bi bi-send me-2"></i>Send Reset Link
        </button>

        <div class="text-center mt-3">
            <a href="{{ route('admin.login') }}" class="text-decoration-none small fw-medium" style="color: #7367f0;">
                <i class="bi bi-arrow-left me-1"></i>Back to Login
            </a>
        </div>
    </form>
@endsection
