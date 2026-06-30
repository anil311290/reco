@extends('layouts.auth')
@section('title', 'Verify Email')
@section('subtitle', 'Please verify your email address')

@section('content')
    <div class="text-center mb-4">
        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;">
            <i class="bi bi-envelope-check text-primary fs-3"></i>
        </div>
        <p class="text-muted small">
            Before continuing, please verify your email address by clicking the link we sent you.
        </p>
    </div>

    @if(session('resent'))
    <div class="alert alert-success small" role="alert">
        <i class="bi bi-check-circle me-2"></i>A fresh verification link has been sent to your email.
    </div>
    @endif

    <form method="POST" action="{{ route('admin.verification.resend') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-login w-100">
            <i class="bi bi-send me-2"></i>Resend Verification Email
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('admin.logout') }}" class="text-decoration-none small fw-medium" style="color: #7367f0;"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
        <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">@csrf</form>
    </div>
@endsection
