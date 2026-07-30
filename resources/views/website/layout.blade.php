<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>@yield('title', $page->title ?? 'Reco - Modern Offline-First Accounting Software')</title>
    <meta name="description" content="@yield('description', $page->meta_description ?? 'Reco is a modern offline-first accounting software for managing business finances. Track income, expenses, invoices, and receivables with ease.')">
    <meta name="keywords" content="accounting software, offline-first, invoicing, GST, financial management, business accounting">
    <meta name="author" content="Reco">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="@yield('title', $page->title ?? 'Reco')">
    <meta property="og:description" content="@yield('description', $page->meta_description ?? 'Modern offline-first accounting software')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('assets/images/og-image.png') }}">
    <meta property="og:site_name" content="Reco">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $page->title ?? 'Reco')">
    <meta name="twitter:description" content="@yield('description', $page->meta_description ?? 'Modern offline-first accounting software')">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">
    
    <!-- Structured Data - Organization -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "Organization",
        "name": "Reco",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('assets/images/logo.png') }}",
        "description": "Modern offline-first accounting software for businesses",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+91-98765-43210",
            "contactType": "support",
            "email": "support@reco.app"
        }
    }
    </script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/apple-touch-icon.png') }}">
    
    <!-- Local Vendor CSS (offline-safe) -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/css/bootstrap-icons.min.css') }}" rel="stylesheet">
    
    <!-- Website CSS -->
    <link href="{{ asset('assets/css/website.css') }}" rel="stylesheet">
    
    @stack('styles')
</head>
<body class="website-body">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('website.home') }}">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Reco" height="36" class="me-2">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('website.home') ? 'active fw-semibold' : '' }}" href="{{ route('website.home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('website.features') ? 'active fw-semibold' : '' }}" href="{{ route('website.features') }}">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('website.pricing') ? 'active fw-semibold' : '' }}" href="{{ route('website.pricing') }}">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('website.faq') ? 'active fw-semibold' : '' }}" href="{{ route('website.faq') }}">FAQ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('website.about') ? 'active fw-semibold' : '' }}" href="{{ route('website.about') }}">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('website.contact') ? 'active fw-semibold' : '' }}" href="{{ route('website.contact') }}">Contact</a>
                    </li>
                    <li class="nav-item d-lg-none"><hr class="dropdown-divider mx-3"></li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-outline-primary btn-sm me-lg-2 w-100 w-lg-auto mb-2 mb-lg-0" href="{{ route('register') }}">Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm w-100 w-lg-auto" href="{{ route('admin.login') }}">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white pt-5 pb-4">
        <div class="container">
            <!-- Newsletter Signup -->
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center">
                    <h5 class="fw-bold mb-3">Stay Updated</h5>
                    <p class="text-light opacity-75 small mb-3">Subscribe to our newsletter for updates and tips.</p>
                    <form class="d-flex gap-2 justify-content-center flex-wrap" id="newsletterForm">
                        @csrf
                        <input type="email" name="email" class="form-control w-auto" placeholder="Enter your email" style="max-width: 250px;" required>
                        <button type="submit" class="btn btn-primary">Subscribe</button>
                    </form>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="Reco" height="36" class="me-2">
                    </div>
                    <p class="text-light opacity-75 small">
                        Modern offline-first accounting software designed for businesses of all sizes. 
                        Manage your finances with confidence, even without internet connectivity.
                    </p>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-semibold mb-3">Product</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('website.features') }}" class="text-light opacity-75 text-decoration-none small">Features</a></li>
                        <li class="mb-2"><a href="{{ route('website.pricing') }}" class="text-light opacity-75 text-decoration-none small">Pricing</a></li>
                        <li class="mb-2"><a href="{{ route('website.faq') }}" class="text-light opacity-75 text-decoration-none small">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-semibold mb-3">Company</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('website.about') }}" class="text-light opacity-75 text-decoration-none small">About Us</a></li>
                        <li class="mb-2"><a href="{{ route('website.contact') }}" class="text-light opacity-75 text-decoration-none small">Contact</a></li>
                        <li class="mb-2"><a href="{{ route('admin.login') }}" class="text-light opacity-75 text-decoration-none small">Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-semibold mb-3">Legal</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="{{ route('website.privacy') }}" class="text-light opacity-75 text-decoration-none small">Privacy Policy</a></li>
                        <li class="mb-2"><a href="{{ route('website.terms') }}" class="text-light opacity-75 text-decoration-none small">Terms & Conditions</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="fw-semibold mb-3">Connect</h6>
                    <div class="d-flex gap-3 social-links">
                        <a href="#" class="text-light opacity-75" aria-label="Facebook"><i class="bi bi-facebook fs-5"></i></a>
                        <a href="#" class="text-light opacity-75" aria-label="Twitter"><i class="bi bi-twitter-x fs-5"></i></a>
                        <a href="#" class="text-light opacity-75" aria-label="LinkedIn"><i class="bi bi-linkedin fs-5"></i></a>
                        <a href="#" class="text-light opacity-75" aria-label="Instagram"><i class="bi bi-instagram fs-5"></i></a>
                    </div>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-light opacity-50 small mb-0">&copy; {{ date('Y') }} Reco. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-light opacity-50 small mb-0">Made with <i class="bi bi-heart-fill text-danger"></i> for businesses</p>
                </div>
            </div>
        </div>
    </footer>
    
    @stack('additional-sections')

    <!-- Local Vendor JS (offline-safe) -->
    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        $('#newsletterForm').on('submit', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            const email = $.trim($form.find('input[name="email"]').val());

            if (!email) {
                return;
            }

            $button.prop('disabled', true);

            $.ajax({
                url: '{{ route('website.contact.submit') }}',
                method: 'POST',
                data: {
                    _token: $form.find('input[name="_token"]').val(),
                    name: 'Newsletter Subscriber',
                    email: email,
                    subject: 'Newsletter Subscription',
                    message: 'Please add this email to the Reco newsletter list.'
                },
                success: function (response) {
                    $form[0].reset();
                    alert(response.message || 'Subscribed successfully.');
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message
                        || xhr.responseJSON?.errors?.email?.[0]
                        || 'Unable to subscribe right now.';
                    alert(message);
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
