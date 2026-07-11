@extends('website.layout')

@section('title', 'Reco - Modern Offline-First Accounting Software')
@section('description', 'Manage your business finances with Reco. Offline-first accounting software for invoicing, GST compliance, financial reports, and more. Works without internet.')

@section('content')
<!-- Hero Section -->
<section class="hero-section py-5">
    <div class="container py-5">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3 fw-semibold">
                    <i class="bi bi-lightning-charge me-1"></i> Offline-First Accounting
                </span>
                <h1 class="display-4 fw-bold text-dark mb-3 lh-sm">
                    Manage Your Business <span class="text-primary">Finances Smarter</span>
                </h1>
                <p class="lead text-muted mb-4">
                    Reco is a modern accounting platform that works offline. Track income, expenses, 
                    invoices, and receivables — all in one place with GST compliance.
                </p>
                <div class="d-flex gap-3 flex-wrap">
<a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-rocket-takeoff me-2"></i>Start Free Trial
                        </a>
                    <a href="{{ route('website.features') }}" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="bi bi-play-circle me-2"></i>View Features
                    </a>
                </div>
                <div class="d-flex align-items-center mt-4 text-muted small">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <span class="me-3">No credit card required</span>
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <span>Admin approval required</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="position-relative">
                    <div class="bg-primary bg-opacity-10 rounded-4 p-5 text-center">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="Reco Dashboard" class="img-fluid hero-illustration" style="max-height: 320px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-4 bg-light">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-primary mb-1">500+</h3>
                <p class="text-muted small mb-0">Active Businesses</p>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-primary mb-1">50K+</h3>
                <p class="text-muted small mb-0">Transactions Processed</p>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-primary mb-1">99.9%</h3>
                <p class="text-muted small mb-0">Uptime Guarantee</p>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-primary mb-1">4.9★</h3>
                <p class="text-muted small mb-0">Customer Rating</p>
            </div>
        </div>
    </div>
</section>

<!-- Features Overview -->
<section class="py-5 section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3">Features</span>
            <h2 class="fw-bold mb-3">Everything You Need to Manage Finances</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                From invoicing to reporting, Reco covers all aspects of business accounting with offline capability.
            </p>
        </div>
        <div class="row g-4 features-grid">
            @php
            $features = [
                ['icon' => 'bi-receipt', 'title' => 'Invoice Management', 'desc' => 'Create professional sales and purchase invoices with GST support.', 'color' => 'primary'],
                ['icon' => 'bi-journal-text', 'title' => 'Voucher System', 'desc' => 'Record income, expense, receipt, payment, and journal vouchers.', 'color' => 'info'],
                ['icon' => 'bi-graph-up', 'title' => 'Financial Reports', 'desc' => 'Generate Balance Sheet, P&L, Trial Balance, Cash Book, and Bank Book reports.', 'color' => 'success'],
                ['icon' => 'bi-people', 'title' => 'Party Management', 'desc' => 'Track debtors and creditors with outstanding aging reports.', 'color' => 'warning'],
                ['icon' => 'bi-bank', 'title' => 'Bank Accounts', 'desc' => 'Manage multiple bank accounts with reconciliation.', 'color' => 'primary'],
                ['icon' => 'bi-cloud-arrow-down', 'title' => 'Offline-First', 'desc' => 'Work without internet. Sync when connected.', 'color' => 'info'],
            ];
            @endphp

            @foreach($features as $feature)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm feature-item">
                    <div class="card-body p-4">
                        <div class="bg-{{ $feature['color'] }} bg-opacity-10 rounded-3 d-inline-flex p-3 mb-3 feature-icon">
                            <i class="bi {{ $feature['icon'] }} text-{{ $feature['color'] }} fs-4"></i>
                        </div>
                        <h5 class="fw-semibold">{{ $feature['title'] }}</h5>
                        <p class="text-muted small mb-0">{{ $feature['desc'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-5">
            <a href="{{ route('website.features') }}" class="btn btn-outline-primary">
                View All Features <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-5 bg-light section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">How It Works</h2>
            <p class="text-muted">Get started in 3 simple steps</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                    <span class="fw-bold fs-4">1</span>
                </div>
                <h5 class="fw-semibold">Register</h5>
                <p class="text-muted small">Create your account with company details. Admin approval required.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                    <span class="fw-bold fs-4">2</span>
                </div>
                <h5 class="fw-semibold">Configure</h5>
                <p class="text-muted small">Set up chart of accounts, parties, and opening balances after approval.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                    <span class="fw-bold fs-4">3</span>
                </div>
                <h5 class="fw-semibold">Start Managing</h5>
                <p class="text-muted small">Record transactions, generate reports, and manage receivables.</p>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Preview -->
@if($plans && $plans->count())
<section class="py-5 section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3">Pricing</span>
            <h2 class="fw-bold mb-3">Simple, Transparent Pricing</h2>
            <p class="text-muted">Choose the plan that fits your business</p>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach($plans->take(3) as $display)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 {{ $display->badge ? 'border-primary shadow pricing-card featured' : 'border-0 shadow-sm pricing-card' }}">
                    @if($display->badge)
                    <div class="card-header bg-primary text-white text-center py-2">
                        <span class="badge bg-white text-primary">{{ $display->badge }}</span>
                    </div>
                    @endif
                    <div class="card-body p-4 text-center">
                        <h5 class="fw-bold">{{ $display->plan->name }}</h5>
                        <p class="text-muted small">{{ $display->description_short ?? $display->plan->description }}</p>
                        <div class="my-4">
                            @if($display->plan->monthly_price > 0)
                            <span class="display-6 fw-bold">₹{{ number_format($display->plan->monthly_price) }}</span>
                            <span class="text-muted">/month</span>
                            @else
                            <span class="display-6 fw-bold text-success">Free</span>
                            @endif
                        </div>
                        @if($display->plan->monthly_price > 0 && $display->plan->yearly_price > 0)
                        <small class="text-success">
                            <i class="bi bi-tag me-1"></i>
                            ₹{{ number_format($display->plan->yearly_price) }}/year (save {{ round((1 - ($display->plan->yearly_price / ($display->plan->monthly_price * 12))) * 100) }}%)
                        </small>
                        @endif
                        @if($display->plan->monthly_price > 0 && $display->plan->lifetime_price > 0)
                        <small class="text-info d-block mt-1">
                            <i class="bi bi-badge-ad me-1"></i>
                            ₹{{ number_format($display->plan->lifetime_price) }} one-time (Lifetime)
                        </small>
                        @endif
                        @if($display->features_list)
                        <ul class="list-unstyled text-start">
                            @foreach($display->features_list as $feature)
                            <li class="mb-2 small">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>{{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                        <a href="{{ route('register') }}?plan={{ $display->plan->slug }}" class="btn {{ $display->badge ? 'btn-primary' : 'btn-outline-primary' }} w-100 mt-3">
                            Get Started
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-4">
            <a href="{{ route('website.pricing') }}" class="btn btn-outline-primary">
                View All Plans <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>
@endif

<!-- Testimonials -->
@if($testimonials && $testimonials->count())
<section class="py-5 bg-light section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3">What Our Customers Say</h2>
            <p class="text-muted">Trusted by businesses across industries</p>
        </div>
        <div class="row g-4">
            @foreach($testimonials as $testimonial)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm testimonial-card">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            @for($i = 0; $i < $testimonial->rating; $i++)
                            <i class="bi bi-star-fill text-warning"></i>
                            @endfor
                        </div>
                        <p class="text-muted small mb-3">"{{ Str::limit($testimonial->testimonial, 150) }}"</p>
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;">
                                <span class="fw-bold text-primary">{{ strtoupper(substr($testimonial->client_name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <h6 class="mb-0 small fw-semibold">{{ $testimonial->client_name }}</h6>
                                <small class="text-muted">{{ $testimonial->designation }}{{ $testimonial->company_name ? ', ' . $testimonial->company_name : '' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- CTA Section -->
<section class="py-5 bg-primary text-white section-padding">
    <div class="container text-center py-4">
        <h2 class="fw-bold mb-3">Ready to Simplify Your Accounting?</h2>
        <p class="mb-4 opacity-75 mx-auto" style="max-width: 500px;">
            Join hundreds of businesses already using Reco to manage their finances efficiently.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ route('register') }}" class="btn btn-light btn-lg px-4">
                <i class="bi bi-rocket-takeoff me-2"></i>Start Free Trial
            </a>
            <a href="{{ route('website.contact') }}" class="btn btn-outline-light btn-lg px-4">
                <i class="bi bi-chat-dots me-2"></i>Contact Sales
            </a>
        </div>
    </div>
</section>

<!-- Scroll to Top Button -->
<button id="scrollTopBtn" aria-label="Scroll to top">
    <i class="bi bi-arrow-up"></i>
</button>

@push('scripts')
<script>
// Scroll to top button
const scrollTopBtn = document.getElementById('scrollTopBtn');
window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        scrollTopBtn.style.display = 'block';
    } else {
        scrollTopBtn.style.display = 'none';
    }
});

scrollTopBtn?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Navbar scroll effect
const navbar = document.querySelector('.navbar');
window.addEventListener('scroll', () => {
    if (window.pageYOffset > 50) {
        navbar.classList.add('navbar-scrolled');
    } else {
        navbar.classList.remove('navbar-scrolled');
    }
});
</script>
@endpush
@endsection
