@extends('website.layout')

@section('title', ($page->title ?? 'Page') . ' - Reco')
@section('description', $page->meta_description ?? 'Learn more about Reco accounting software')

@section('content')
<!-- Page Header -->
<section class="page-header bg-light">
    <div class="container text-center py-3">
        <h1 class="fw-bold mb-2">{{ $page->title }}</h1>
        @if($page->meta_description)
        <p class="text-muted mb-0">{{ $page->meta_description }}</p>
        @endif
    </div>
</section>

<!-- Page Content -->
<section class="py-5 section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if($page->content)
                    <div class="page-content">
                        {!! $page->content !!}
                    </div>
                @else
                    @if($page->slug === 'features')
                    <!-- Default Features Content -->
                    <div class="text-center mb-5">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3">Features</span>
                        <h2 class="fw-bold mb-3">Complete Accounting Solution</h2>
                        <p class="text-muted">Powerful tools for modern businesses</p>
                    </div>
                    <div class="row g-4 features-grid">
                        @php
                        $features = [
                            ['icon' => 'bi-receipt-cutoff', 'title' => 'Sales Invoicing', 'desc' => 'Create professional sales invoices with GST calculations, multiple line items, and automatic ledger entries.', 'color' => 'primary'],
                            ['icon' => 'bi-bag-check', 'title' => 'Purchase Invoicing', 'desc' => 'Record purchase invoices with supplier details, tax breakdowns, and inventory tracking.', 'color' => 'info'],
                            ['icon' => 'bi-cash-stack', 'title' => 'Receipt & Payment', 'desc' => 'Record cash and bank receipts and payments with automatic balance updates.', 'color' => 'success'],
                            ['icon' => 'bi-journal-bookmark', 'title' => 'Journal Vouchers', 'desc' => 'Create journal entries for adjustments, transfers, and complex transactions.', 'color' => 'warning'],
                            ['icon' => 'bi-people-fill', 'title' => 'Party Management', 'desc' => 'Manage debtors and creditors with detailed profiles, credit limits, and outstanding tracking.', 'color' => 'primary'],
                            ['icon' => 'bi-bank2', 'title' => 'Bank Reconciliation', 'desc' => 'Track multiple bank accounts with reconciliation and balance statements.', 'color' => 'info'],
                            ['icon' => 'bi-file-earmark-bar-graph', 'title' => 'Financial Reports', 'desc' => 'Generate Balance Sheet, Profit & Loss, Trial Balance, Cash Flow, and Day Book reports.', 'color' => 'success'],
                            ['icon' => 'bi-clock-history', 'title' => 'Aging Reports', 'desc' => 'Track receivables and payables aging to manage cash flow effectively.', 'color' => 'warning'],
                            ['icon' => 'bi-cloud-arrow-down', 'title' => 'Offline-First', 'desc' => 'Work without internet connectivity. Data syncs automatically when connection is restored.', 'color' => 'primary'],
                            ['icon' => 'bi-shield-lock', 'title' => 'Role-Based Access', 'desc' => 'Control who can view, create, edit, or delete with granular permission settings.', 'color' => 'info'],
                            ['icon' => 'bi-file-earmark-pdf', 'title' => 'Export to PDF & Excel', 'desc' => 'Export any report or document to PDF or Excel format for sharing and printing.', 'color' => 'success'],
                            ['icon' => 'bi-phone', 'title' => 'Mobile App', 'desc' => 'Access your accounts on the go with our companion mobile application.', 'color' => 'warning'],
                        ];
                        @endphp

                        @foreach($features as $feature)
                        <div class="col-md-6">
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

                    @elseif($page->slug === 'about')
                    <!-- Default About Content -->
                    <div class="text-center mb-5">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3">About Us</span>
                        <h2 class="fw-bold mb-3">About Reco</h2>
                        <p class="text-muted">We're building the future of accounting for small and medium businesses.</p>
                    </div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold"><i class="bi bi-bullseye text-primary me-2"></i>Our Mission</h5>
                                    <p class="text-muted small">
                                        To empower businesses with simple, reliable, and affordable accounting tools that work 
                                        even without an internet connection. We believe every business deserves access to 
                                        professional-grade financial management.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold"><i class="bi bi-eye text-primary me-2"></i>Our Vision</h5>
                                    <p class="text-muted small">
                                        To become the go-to accounting platform for businesses across emerging markets, 
                                        where reliable internet isn't always available but business never stops.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                        <p class="text-muted mt-3">Content is being prepared. Check back soon!</p>
                    </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
