<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Reco') }} - @yield('title', 'Login')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">

    <!-- Local Vendor CSS (offline-safe) -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/css/bootstrap-icons.min.css') }}" rel="stylesheet">
    
    <!-- Login CSS -->
    <link href="{{ asset('assets/css/login.css') }}" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-5 col-xl-4">
                
                <!-- Auth Card -->
                <div class="card login-card border-0">
                    <div class="card-body p-4 p-sm-5">
                        
                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="Reco" width="100" class="mb-3">
                            <h1 class="fw-bold text-dark mb-1" style="font-size: 22px;">@yield('title', 'Welcome Back')</h1>
                            @hasSection('subtitle')
                            <p class="text-muted small mb-0">@yield('subtitle')</p>
                            @endif
                        </div>

                        <!-- Session Messages -->
                        @if(session('status'))
                        <div class="alert alert-success alert-dismissible fade show small" role="alert">
                            <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
                            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show small" role="alert">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        @yield('content')

                    </div>
                </div>

                <!-- Footer -->
                <p class="text-center text-muted small mt-4 mb-0">
                    &copy; {{ date('Y') }} Reco. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <!-- Local Vendor JS (offline-safe) -->
    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    @stack('scripts')
</body>
</html>
