<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Reco') }} - @yield('title', 'Admin')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/logo.png') }}">

    <!-- Local Vendor CSS (offline-safe) -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/css/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/datatables/css/responsive.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/datatables/css/buttons.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/select2/css/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/toastr/toastr.min.css') }}" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    
    <!-- Dynamic Theme CSS -->
    @php
        $companyId = auth()->check() ? auth()->user()->company_id : null;
        $themeService = app(\App\Services\SettingsService::class);
        $themeCss = $themeService->getThemeCss($companyId);
        $darkMode = \App\Models\Setting::getValue('theme.dark_mode', '0', $companyId);
    @endphp
    @if($themeCss)
    <style id="dynamic-theme">{!! $themeCss !!}</style>
    @endif

    <style>
        body.dark-mode .table,
        body.dark-mode .table-responsive,
        body.dark-mode table.dataTable {
            color: #e9ecef;
            background-color: #1d1f2a;
        }

        body.dark-mode .table thead th,
        body.dark-mode .table thead td {
            background-color: #252a3b;
            color: #f8f9ff;
            border-color: rgba(255,255,255,.12);
        }

        body.dark-mode .table tbody tr,
        body.dark-mode .table tbody td {
            background-color: #1d1f2a;
            color: #e9ecef;
            border-color: rgba(255,255,255,.08);
        }

        body.dark-mode .table-hover tbody tr:hover {
            background-color: rgba(255,255,255,0.06);
        }

        body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255,255,255,0.03);
        }

        body.dark-mode .dataTables_wrapper .dataTables_filter input,
        body.dark-mode .dataTables_wrapper .dataTables_length select,
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
            background-color: #1b1d2a;
            color: #e9ecef;
            border-color: rgba(255,255,255,.12);
        }

        body.dark-mode .dataTables_wrapper .dataTables_info,
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #ced4da;
        }

        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: rgba(255,255,255,0.08);
            color: #ffffff;
        }

        body.dark-mode .dt-buttons .btn {
            background-color: #1b1d2a;
            border-color: rgba(255,255,255,.12);
            color: #e9ecef;
        }

        .dt-buttons {
            gap: 8px;
            display: inline-flex;
            flex-wrap: wrap;
        }

        .dt-buttons .btn {
            border-radius: .5rem;
            font-size: .8125rem;
            font-weight: 500;
            padding: .35rem .75rem;
        }

        .dt-buttons .dt-export-btn {
            border-radius: 12px;
            font-size: .82rem;
            font-weight: 700;
            padding: .42rem .85rem;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .12);
            border-width: 1px;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
        }

        .dt-buttons.btn-group > .btn,
        .dt-buttons.btn-group > .btn:not(:first-child),
        .dt-buttons.btn-group > .btn:not(:last-child):not(.dropdown-toggle) {
            border-radius: 12px !important;
            margin-right: 8px;
        }

        .dt-buttons.btn-group > .btn:last-child {
            margin-right: 0;
        }

        .dt-buttons .dt-export-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, .18);
            filter: saturate(1.05);
        }

        .dt-buttons .dt-export-excel {
            background: linear-gradient(135deg, #16a34a, #15803d);
            border-color: #15803d;
            color: #fff;
        }

        .dt-buttons .dt-export-pdf {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-color: #dc2626;
            color: #fff;
        }

        body.dark-mode .dt-buttons .dt-export-btn {
            box-shadow: 0 12px 26px rgba(2, 6, 23, .45);
        }
    </style>
    
    @stack('styles')
</head>
<body class="admin-body">
    <!-- Sidebar Backdrop (mobile) -->
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <!-- Full logo (visible when expanded) -->
                    <svg class="sidebar-logo-text" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 180 40" fill="none" style="height: 30px; width: auto;">
                        <rect x="2" y="18" width="6" height="18" rx="2" fill="#1f6feb"/>
                        <rect x="10" y="11" width="6" height="25" rx="2" fill="#58a6ff"/>
                        <rect x="18" y="4" width="6" height="32" rx="2" fill="#9bc9ff"/>
                        <text x="30" y="28" font-family="Inter, sans-serif" font-size="17" font-weight="700" fill="#4b4b4b">Re</text>
                        <text x="52" y="28" font-family="Inter, sans-serif" font-size="17" font-weight="400" fill="#1f6feb">co</text>
                    </svg>
                    <!-- Compact icon logo (visible when collapsed) -->
                    <svg class="sidebar-logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none" style="height: 28px; width: 28px; display: none;">
                        <rect x="5" y="16" width="5" height="12" rx="1.5" fill="#1f6feb"/>
                        <rect x="13" y="10" width="5" height="18" rx="1.5" fill="#58a6ff"/>
                        <rect x="21" y="4" width="5" height="24" rx="1.5" fill="#9bc9ff"/>
                    </svg>
                </a>
            </div>
            <button type="button" id="sidebarCollapse" class="btn btn-link sidebar-toggle" title="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
        </div>

        <div class="sidebar-menu">
            @php($isSuperAdmin = auth()->user()->isSuperAdmin())
            <ul class="nav flex-column">
                <li class="sidebar-section"><span>Dashboards</span></li>
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-house-door"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if($isSuperAdmin)
                <li class="sidebar-section"><span>Platform</span></li>
                <li class="nav-item">
                    <a class="nav-link has-submenu {{ request()->is('admin/companies*') ? 'active' : '' }}" href="#companiesSubmenu" data-bs-toggle="collapse">
                        <i class="bi bi-buildings"></i>
                        <span>Companies</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->is('admin/companies*') ? 'show' : '' }}" id="companiesSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.companies.index', 'admin.companies.show', 'admin.companies.edit') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}">
                                    <i class="bi bi-building"></i>
                                    <span>All Companies</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.companies.approval') ? 'active' : '' }}" href="{{ route('admin.companies.approval') }}">
                                    <i class="bi bi-building-check"></i>
                                    <span>Company Approvals</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link has-submenu {{ request()->is('admin/platform/subscriptions*') || request()->is('admin/platform/payments*') || request()->is('admin/subscription-plans*') ? 'active' : '' }}" href="#subscriptionsSubmenu" data-bs-toggle="collapse">
                        <i class="bi bi-credit-card-2-front"></i>
                        <span>Subscriptions</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->is('admin/platform/subscriptions*') || request()->is('admin/platform/payments*') || request()->is('admin/subscription-plans*') ? 'show' : '' }}" id="subscriptionsSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.platform-subscriptions.index') ? 'active' : '' }}" href="{{ route('admin.platform-subscriptions.index') }}">
                                    <i class="bi bi-collection"></i>
                                    <span>All Subscriptions</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.subscription-plans.*') ? 'active' : '' }}" href="{{ route('admin.subscription-plans.index') }}">
                                    <i class="bi bi-grid-3x3-gap"></i>
                                    <span>Plans</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.platform-subscriptions.payments') ? 'active' : '' }}" href="{{ route('admin.platform-subscriptions.payments') }}">
                                    <i class="bi bi-cash-coin"></i>
                                    <span>Payments</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="sidebar-section"><span>Website</span></li>
                <li class="nav-item">
                    <a class="nav-link has-submenu {{ request()->is('admin/cms*') || request()->is('admin/contacts*') ? 'active' : '' }}" href="#cmsSubmenu" data-bs-toggle="collapse">
                        <i class="bi bi-globe"></i>
                        <span>Website CMS</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->is('admin/cms*') || request()->is('admin/contacts*') ? 'show' : '' }}" id="cmsSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.cms.pages.*') ? 'active' : '' }}" href="{{ route('admin.cms.pages.index') }}">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>Pages</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.cms.faqs.*') ? 'active' : '' }}" href="{{ route('admin.cms.faqs.index') }}">
                                    <i class="bi bi-question-circle"></i>
                                    <span>FAQs</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.cms.testimonials.*') ? 'active' : '' }}" href="{{ route('admin.cms.testimonials.index') }}">
                                    <i class="bi bi-chat-quote"></i>
                                    <span>Testimonials</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}" href="{{ route('admin.contacts.index') }}">
                                    <i class="bi bi-envelope"></i>
                                    <span>Contact Submissions</span>
                                    <span class="badge bg-danger ms-auto contact-badge d-none">0</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="sidebar-section"><span>Configuration</span></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.themes.*') ? 'active' : '' }}" href="{{ route('admin.themes.index') }}">
                        <i class="bi bi-palette"></i>
                        <span>Theme</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                        <i class="bi bi-clock-history"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
                @else
                <li class="sidebar-section"><span>Masters</span></li>
                <!-- Masters -->
                @anyrole('admin', 'manager', 'accountant')
                <li class="nav-item">
                    <a class="nav-link has-submenu {{ request()->is('admin/accounts*') || request()->is('admin/parties*') || request()->is('admin/items*') || request()->is('admin/item-categories*') || request()->is('admin/tax-rates*') || request()->is('admin/bank-accounts*') ? 'active' : '' }}" href="#mastersSubmenu" data-bs-toggle="collapse">
                        <i class="bi bi-database"></i>
                        <span>Masters</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->is('admin/accounts*') || request()->is('admin/parties*') || request()->is('admin/items*') || request()->is('admin/item-categories*') || request()->is('admin/tax-rates*') ? 'show' : '' }}" id="mastersSubmenu">
                        <ul class="nav flex-column">
                            @permission('accounts.view')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}" href="{{ route('admin.accounts.index') }}">
                                    <i class="bi bi-journal-text"></i>
                                    <span>Ledgers</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('parties.view')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.parties.*') ? 'active' : '' }}" href="{{ route('admin.parties.index') }}">
                                    <i class="bi bi-people"></i>
                                    <span>AR/AP</span>
                                </a>
                            </li>
                            @endpermission
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.items.*') ? 'active' : '' }}" href="{{ route('admin.items.index') }}">
                                    <i class="bi bi-box-seam"></i>
                                    <span>Items</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.item-categories.*') ? 'active' : '' }}" href="{{ route('admin.item-categories.index') }}">
                                    <i class="bi bi-tags"></i>
                                    <span>Item Categories</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.tax-rates.*') ? 'active' : '' }}" href="{{ route('admin.tax-rates.index') }}">
                                    <i class="bi bi-percent"></i>
                                    <span>Tax Rates</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endanyrole

                <li class="sidebar-section"><span>Transactions</span></li>
                <!-- Vouchers -->
                @anyrole('admin', 'manager', 'accountant')
                <li class="nav-item">
                    <a class="nav-link has-submenu {{ request()->is('admin/vouchers*') || request()->is('admin/sales-invoices*') || request()->is('admin/service-sales-invoices*') || request()->is('admin/purchase-invoices*') ? 'active' : '' }}" href="#vouchersSubmenu" data-bs-toggle="collapse">
                        <i class="bi bi-receipt"></i>
                        <span>Vouchers</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->is('admin/vouchers*') || request()->is('admin/sales-invoices*') || request()->is('admin/service-sales-invoices*') || request()->is('admin/purchase-invoices*') ? 'show' : '' }}" id="vouchersSubmenu">
                        <ul class="nav flex-column">
                            @permission('vouchers.view')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.vouchers.index') ? 'active' : '' }}" href="{{ route('admin.vouchers.index') }}">
                                    <i class="bi bi-list-ul"></i>
                                    <span>All Vouchers</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('vouchers.view')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.sales-invoices.*') ? 'active' : '' }}" href="{{ route('admin.sales-invoices.index') }}">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>Item Sale Invoices</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.service-sales-invoices.*') ? 'active' : '' }}" href="{{ route('admin.service-sales-invoices.index') }}">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>Service Sale Invoices</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.purchase-invoices.*') ? 'active' : '' }}" href="{{ route('admin.purchase-invoices.index') }}">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>Purchase Invoices</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/vouchers/type/payment') ? 'active' : '' }}" href="{{ route('admin.vouchers.type', 'payment') }}">
                                    <i class="bi bi-wallet2"></i>
                                    <span>Payments</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/vouchers/type/receipt') ? 'active' : '' }}" href="{{ route('admin.vouchers.type', 'receipt') }}">
                                    <i class="bi bi-cash-stack"></i>
                                    <span>Receipts</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/vouchers/type/journal') ? 'active' : '' }}" href="{{ route('admin.vouchers.type', 'journal') }}">
                                    <i class="bi bi-journal-bookmark"></i>
                                    <span>Adjustments</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                @endanyrole

                <li class="sidebar-section"><span>Analytics</span></li>
                <!-- Reports -->
                <li class="nav-item">
                    <a class="nav-link has-submenu {{ request()->is('admin/reports*') ? 'active' : '' }}" href="#reportsSubmenu" data-bs-toggle="collapse">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Reports</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->is('admin/reports*') ? 'show' : '' }}" id="reportsSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.balance-sheet') ? 'active' : '' }}" href="{{ route('admin.reports.balance-sheet') }}">
                                    <i class="bi bi-file-earmark-bar-graph"></i>
                                    <span>Balance Sheet</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.profit-loss') ? 'active' : '' }}" href="{{ route('admin.reports.profit-loss') }}">
                                    <i class="bi bi-graph-up"></i>
                                    <span>Profit & Loss</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.cash-flow') ? 'active' : '' }}" href="{{ route('admin.reports.cash-flow') }}">
                                    <i class="bi bi-currency-exchange"></i>
                                    <span>Cash Flow</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.trial-balance') ? 'active' : '' }}" href="{{ route('admin.reports.trial-balance') }}">
                                    <i class="bi bi-journal-check"></i>
                                    <span>Trial Balance</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.day-book') ? 'active' : '' }}" href="{{ route('admin.reports.day-book') }}">
                                    <i class="bi bi-calendar-day"></i>
                                    <span>Day Book</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.ledger') ? 'active' : '' }}" href="{{ route('admin.reports.ledger') }}">
                                    <i class="bi bi-book"></i>
                                    <span>Ledgers</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.debtors-outstanding') ? 'active' : '' }}" href="{{ route('admin.reports.debtors-outstanding') }}">
                                    <i class="bi bi-people"></i>
                                    <span>AR Aging</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.creditors-outstanding') ? 'active' : '' }}" href="{{ route('admin.reports.creditors-outstanding') }}">
                                    <i class="bi bi-people-fill"></i>
                                    <span>AP Aging</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="sidebar-section"><span>Configuration</span></li>
                @permission('audit-logs.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                        <i class="bi bi-clock-history"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
                @endpermission

                <!-- Settings -->
                <li class="nav-item">
                    <a class="nav-link has-submenu {{ request()->is('admin/settings*') || request()->is('admin/financial-years*') || request()->is('admin/themes*') || request()->is('admin/subscriptions*') ? 'active' : '' }}" href="#settingsSubmenu" data-bs-toggle="collapse">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->is('admin/settings*') || request()->is('admin/financial-years*') || request()->is('admin/themes*') || request()->is('admin/subscriptions*') ? 'show' : '' }}" id="settingsSubmenu">
                        <ul class="nav flex-column">
                            @permission('settings.view')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                                    <i class="bi bi-building"></i>
                                    <span>Company</span>
                                </a>
                            </li>
                            @endpermission
                            @permission('financial-years.view')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.financial-years.*') ? 'active' : '' }}" href="{{ route('admin.financial-years.index') }}">
                                    <i class="bi bi-calendar-event"></i>
                                    <span>Financial Years</span>
                                </a>
                            </li>
                            @endpermission
                        </ul>
                    </div>
                </li>
                @endif
            </ul>
        </div>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="user-details">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">{{ str(auth()->user()->role)->replace('_', ' ')->title() }}</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div id="content" class="content">
        <!-- Top Header -->
        <header class="header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <button type="button" id="sidebarCollapseBtn" class="mobile-toggle" aria-label="Toggle sidebar">
                            <i class="bi bi-list"></i>
                        </button>
                    </div>
                    <div class="col">
                        <h1 class="header-title">@yield('title', 'Dashboard')</h1>
                    </div>
                    <div class="col-auto">
                        <div class="header-actions">
                            <!-- Dark Mode Toggle -->
                            <button type="button" id="darkModeToggle" class="btn btn-link" title="Toggle dark mode">
                                <i class="bi {{ $darkMode === '1' ? 'bi-sun' : 'bi-moon' }}"></i>
                            </button>

                            <!-- Notifications -->
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-bell"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        3
                                    </span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                                    <h6 class="dropdown-header">Notifications</h6>
                                    <a class="dropdown-item" href="#">
                                        <i class="bi bi-info-circle text-info"></i>
                                        <span>New voucher created</span>
                                    </a>
                                    <a class="dropdown-item" href="#">
                                        <i class="bi bi-check-circle text-success"></i>
                                        <span>Report generated</span>
                                    </a>
                                    <a class="dropdown-item" href="#">
                                        <i class="bi bi-exclamation-triangle text-warning"></i>
                                        <span>Payment overdue</span>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-center" href="#">View all notifications</a>
                                </div>
                            </div>

                            <!-- User Menu -->
                            <div class="dropdown d-inline-block">
                                <button class="btn user-menu-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="user-menu-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                    <span class="user-menu-name d-none d-md-inline">{{ auth()->user()->name }}</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end user-dropdown">
                                    <div class="dropdown-user-info">
                                        <span class="dropdown-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                        <div>
                                            <div class="dropdown-user-name">{{ auth()->user()->name }}</div>
                                            <div class="dropdown-user-email">{{ auth()->user()->email }}</div>
                                        </div>
                                    </div>
                                    <hr class="dropdown-divider">
                                    <a class="dropdown-item" href="#">
                                        <i class="bi bi-person"></i>
                                        <span>My Profile</span>
                                    </a>
                                    <a class="dropdown-item" href="{{ $isSuperAdmin ? route('admin.subscription-plans.index') : route('admin.settings.index') }}">
                                        <i class="bi bi-gear"></i>
                                        <span>{{ $isSuperAdmin ? 'Platform Settings' : 'Settings' }}</span>
                                    </a>
                                    <a class="dropdown-item" href="#">
                                        <i class="bi bi-key"></i>
                                        <span>Change Password</span>
                                    </a>
                                    <hr class="dropdown-divider">
                                    <form action="{{ route('admin.logout') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-box-arrow-right"></i>
                                            <span>Logout</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="main-content">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show alert-auto-hide" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show alert-auto-hide" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <span>&copy; {{ date('Y') }} Reco. All rights reserved.</span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span>Version 1.0.0</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <!-- Local Vendor JS (offline-safe) -->
    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/responsive.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/moment/moment.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/chartjs/chart.umd.min.js') }}"></script>
    <script>
        // Some bundled scripts expose a global "module" object in browser.
        // Toastr's UMD loader may pick that branch and call require('jquery').
        // Temporarily unset it so toastr always binds to window.jQuery.
        window.__recoModuleBackup = window.module;
        window.module = undefined;
    </script>
    <script src="{{ asset('assets/vendor/toastr/toastr.min.js') }}"></script>
    <script>
        if (typeof window.__recoModuleBackup !== 'undefined') {
            window.module = window.__recoModuleBackup;
        } else {
            try {
                delete window.module;
            } catch (e) {
                window.module = undefined;
            }
        }
        delete window.__recoModuleBackup;
    </script>
    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    
    <!-- Custom JS -->
    <script src="{{ asset('assets/js/common.js') }}"></script>

    <script>
        // Sidebar Toggle
        $(document).ready(function() {
            const sidebar = $('#sidebar');
            const content = $('#content');
            const backdrop = $('#sidebarBackdrop');
            const logoText = $('.sidebar-logo-text');
            const logoIcon = $('.sidebar-logo-icon');

            function setCollapsedState(collapsed) {
                if (collapsed) {
                    sidebar.addClass('collapsed');
                    content.addClass('sidebar-collapsed');
                    logoText.css({ opacity: 0, width: 0, overflow: 'hidden' });
                    logoIcon.css('display', 'block');
                } else {
                    sidebar.removeClass('collapsed');
                    content.removeClass('sidebar-collapsed');
                    logoText.css({ opacity: 1, width: 'auto', overflow: 'visible' });
                    logoIcon.css('display', 'none');
                }
            }

            function handleResize() {
                const isMobile = window.innerWidth < 992;
                if (isMobile) {
                    // On mobile: remove collapsed (desktop mode), use active instead
                    sidebar.removeClass('collapsed');
                    content.removeClass('sidebar-collapsed');
                    logoText.css({ opacity: 1, width: 'auto', overflow: 'visible' });
                    logoIcon.css('display', 'none');
                } else {
                    // On desktop: remove mobile active state
                    sidebar.removeClass('active');
                    backdrop.removeClass('show');
                }
            }

            // Toggle on button click
            $('#sidebarCollapse, #sidebarCollapseBtn').on('click', function() {
                const isMobile = window.innerWidth < 992;
                if (isMobile) {
                    sidebar.toggleClass('active');
                    backdrop.toggleClass('show');
                } else {
                    const shouldCollapse = !sidebar.hasClass('collapsed');
                    setCollapsedState(shouldCollapse);
                }
            });

            // Close mobile sidebar on backdrop click
            backdrop.on('click', function() {
                sidebar.removeClass('active');
                backdrop.removeClass('show');
            });

            // Submenu toggle is handled by Bootstrap's data-bs-toggle="collapse"
            // No additional jQuery handler needed

            // Handle resize
            $(window).on('resize', handleResize);
            handleResize();

            // Dark Mode Toggle
            var isDarkMode = {{ $darkMode === '1' ? 'true' : 'false' }};
            if (isDarkMode) { $('body').addClass('dark-mode'); }

            $('#darkModeToggle').on('click', function() {
                var isDark = $('body').hasClass('dark-mode');
                var newMode = isDark ? '0' : '1';
                $('body').toggleClass('dark-mode');
                $(this).find('i').toggleClass('bi-moon bi-sun');
                $.ajax({
                    url: '{{ route("admin.settings.theme") }}',
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        primary_color: '{{ \App\Models\Setting::getValue("theme.primary_color", "#1f6feb", $companyId) }}',
                        secondary_color: '{{ \App\Models\Setting::getValue("theme.secondary_color", "#a8aaae", $companyId) }}',
                        sidebar_color: '{{ \App\Models\Setting::getValue("theme.sidebar_color", "#ffffff", $companyId) }}',
                        header_color: '{{ \App\Models\Setting::getValue("theme.header_color", "#ffffff", $companyId) }}',
                        dark_mode: newMode
                    }
                });
            });
        });
    </script>

    <!-- Contact Submissions Badge -->
    <script>
    $(document).ready(function() {
        if ($('.contact-badge').length) {
            $.get('{{ route("admin.contacts.counts") }}', function(data) {
                if (data.new > 0) {
                    $('.contact-badge').removeClass('d-none').text(data.new);
                }
            });
        }
    });
    </script>
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>
