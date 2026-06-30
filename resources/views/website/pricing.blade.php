@extends('website.layout')

@section('title', 'Pricing Plans - Reco')
@section('description', 'Choose the perfect plan for your business. Flexible pricing with monthly, yearly, and lifetime subscription options.')

@section('content')
<!-- Page Header -->
<section class="page-header bg-light">
    <div class="container text-center py-3">
        <h1 class="fw-bold mb-2">{{ $page->title ?? 'Pricing Plans' }}</h1>
        <p class="text-muted mb-0">{{ $page->meta_description ?? 'Simple, transparent pricing for businesses of all sizes' }}</p>
    </div>
</section>

<!-- Pricing Plans -->
<section class="py-5 section-padding">
    <div class="container">
        @if($plans && $plans->count())
        <div class="row g-4 justify-content-center align-items-stretch">
            @foreach($plans as $display)
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 {{ $display->badge ? 'border-primary shadow pricing-card featured' : 'border-0 shadow-sm pricing-card' }}">
                    @if($display->badge)
                    <div class="card-header bg-primary text-white text-center py-2">
                        <span class="badge bg-white text-primary fw-semibold">{{ $display->badge }}</span>
                    </div>
                    @endif
                    <div class="card-body p-4 text-center d-flex flex-column">
                        <h4 class="fw-bold mb-2">{{ $display->plan->name }}</h4>
                        <p class="text-muted small mb-4">{{ $display->description_short ?? $display->plan->description }}</p>
                        
                        <div class="mb-4">
                            @if($display->plan->monthly_price > 0)
                            <div class="mb-2">
                                <span class="display-5 fw-bold">₹{{ number_format($display->plan->monthly_price) }}</span>
                                <span class="text-muted">/month</span>
                            </div>
                            
                            @if($display->plan->yearly_price > 0)
                            <small class="text-success d-block mb-1">
                                <i class="bi bi-tag me-1"></i>
                                ₹{{ number_format($display->plan->yearly_price) }}/year (save {{ round((1 - ($display->plan->yearly_price / ($display->plan->monthly_price * 12))) * 100) }}%)
                            </small>
                            @endif
                            
                            @if($display->plan->lifetime_price > 0)
                            <small class="text-info d-block mb-2">
                                <i class="bi bi-badge-ad me-1"></i>
                                ₹{{ number_format($display->plan->lifetime_price) }} one-time <strong>(Lifetime)</strong>
                            </small>
                            @endif
                            
                            @else
                            <div class="mb-2">
                                <span class="display-5 fw-bold text-success">Free</span>
                            </div>
                            @endif
                        </div>
                        
                        @if($display->plan->trial_days)
                        <div class="mb-3">
                            <span class="badge bg-success bg-opacity-10 text-success">
                                <i class="bi bi-clock me-1"></i>{{ $display->plan->trial_days }}-day free trial
                            </span>
                        </div>
                        @endif
                        
                        @if($display->features_list)
                        <ul class="list-unstyled text-start mb-4 flex-grow-1">
                            @foreach($display->features_list as $feature)
                            <li class="mb-2 small">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>{{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                        
                        <div class="mt-auto">
                            <a href="{{ route('register') }}?plan={{ $display->plan->slug }}" class="btn {{ $display->badge ? 'btn-primary' : 'btn-outline-primary' }} w-100 btn-lg">
                                Get Started
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-box fs-1 text-muted"></i>
            <p class="text-muted mt-3">Pricing plans coming soon. Contact us for details.</p>
            <a href="{{ route('website.contact') }}" class="btn btn-primary">Contact Us</a>
        </div>
        @endif

        <!-- FAQ Link -->
        <div class="text-center mt-5 pt-4 border-top">
            <p class="text-muted mb-2">Have questions about our pricing?</p>
            <a href="{{ route('website.faq') }}" class="btn btn-outline-secondary">
                <i class="bi bi-question-circle me-2"></i>View FAQ
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white section-padding">
    <div class="container text-center py-4">
        <h2 class="fw-bold mb-3">Need a Custom Solution?</h2>
        <p class="mb-4 opacity-75 mx-auto" style="max-width: 500px;">
            Contact our sales team for custom pricing tailored to your business needs.
        </p>
        <a href="{{ route('website.contact') }}" class="btn btn-light btn-lg px-4">
            <i class="bi bi-chat-dots me-2"></i>Contact Sales
        </a>
    </div>
</section>
@endsection
