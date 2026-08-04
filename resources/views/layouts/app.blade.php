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
        /* Dark-mode tables: transparent so they sit seamlessly on the card (no double background) */
        body.dark-mode .table,
        body.dark-mode .table-responsive,
        body.dark-mode table.dataTable {
            --bs-table-bg: transparent;
            --bs-table-color: var(--lp-text);
            --bs-table-striped-bg: rgba(255,255,255,0.03);
            --bs-table-striped-color: var(--lp-text);
            --bs-table-hover-bg: rgba(255,255,255,0.06);
            --bs-table-hover-color: var(--lp-heading);
            background-color: transparent;
            color: var(--lp-text);
        }

        body.dark-mode .table thead th,
        body.dark-mode .table thead td {
            background-color: var(--lp-muted-bg);
            color: var(--lp-heading);
            border-color: var(--lp-border);
        }

        body.dark-mode .table tbody tr,
        body.dark-mode .table tbody td,
        body.dark-mode .table tbody th {
            background-color: transparent;
            color: var(--lp-text);
            border-color: var(--lp-border-light);
        }

        body.dark-mode .table.table-light thead th,
        body.dark-mode .table .table-light th,
        body.dark-mode .table .table-light td {
            background-color: var(--lp-muted-bg);
            color: var(--lp-heading);
        }

        body.dark-mode .table-hover tbody tr:hover > * {
            background-color: rgba(255,255,255,0.06);
            color: var(--lp-heading);
        }

        body.dark-mode .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: rgba(255,255,255,0.03);
            color: var(--lp-text);
        }

        body.dark-mode .dataTables_wrapper .dataTables_filter input,
        body.dark-mode .dataTables_wrapper .dataTables_length select,
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
            background-color: var(--lp-hover);
            color: var(--lp-text);
            border-color: var(--lp-border);
        }

        body.dark-mode .dataTables_wrapper .dataTables_info,
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--lp-text-sec);
        }

        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: rgba(255,255,255,0.08);
            color: #ffffff;
            border-color: var(--lp-border);
        }

        body.dark-mode .dt-buttons .btn {
            background-color: var(--lp-hover);
            border-color: var(--lp-border);
            color: var(--lp-text);
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
<body class="admin-body{{ $darkMode === '1' ? ' dark-mode' : '' }}">
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
            @php
                $isSuperAdmin = auth()->user()->isSuperAdmin();
            @endphp
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

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.support-tickets.*') ? 'active' : '' }}" href="{{ route('admin.support-tickets.index') }}">
                        <i class="bi bi-headset"></i>
                        <span>Support Inbox</span>
                    </a>
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
                    <a class="nav-link has-submenu {{ request()->is('admin/accounts*') || request()->is('admin/parties*') || request()->is('admin/items*') || request()->is('admin/item-categories*') || request()->is('admin/tax-rates*') ? 'active' : '' }}" href="#mastersSubmenu" data-bs-toggle="collapse">
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
                    <a class="nav-link has-submenu {{ request()->is('admin/vouchers*') || request()->is('admin/sales-invoices*') || request()->is('admin/purchase-invoices*') ? 'active' : '' }}" href="#vouchersSubmenu" data-bs-toggle="collapse">
                        <i class="bi bi-receipt"></i>
                        <span>Vouchers</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->is('admin/vouchers*') || request()->is('admin/sales-invoices*') || request()->is('admin/purchase-invoices*') ? 'show' : '' }}" id="vouchersSubmenu">
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
                                    <span>Sales Invoice</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.purchase-invoices.*') ? 'active' : '' }}" href="{{ route('admin.purchase-invoices.index') }}">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>Purchase Invoices</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ (request()->routeIs('admin.vouchers.type') || request()->routeIs('admin.vouchers.create')) && request()->route('type') === 'payment' ? 'active' : '' }}" href="{{ route('admin.vouchers.type', 'payment') }}">
                                    <i class="bi bi-wallet2"></i>
                                    <span>Payments</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ (request()->routeIs('admin.vouchers.type') || request()->routeIs('admin.vouchers.create')) && request()->route('type') === 'receipt' ? 'active' : '' }}" href="{{ route('admin.vouchers.type', 'receipt') }}">
                                    <i class="bi bi-cash-stack"></i>
                                    <span>Receipts</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ (request()->routeIs('admin.vouchers.type') || request()->routeIs('admin.vouchers.create')) && request()->route('type') === 'journal' ? 'active' : '' }}" href="{{ route('admin.vouchers.type', 'journal') }}">
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
                                <a class="nav-link {{ request()->routeIs('admin.reports.day-book') ? 'active' : '' }}" href="{{ route('admin.reports.day-book') }}">
                                    <i class="bi bi-calendar-day"></i>
                                    <span>Day Book</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.ledger') ? 'active' : '' }}" href="{{ route('admin.reports.ledger') }}">
                                    <i class="bi bi-book"></i>
                                    <span>Ledger</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.trial-balance') ? 'active' : '' }}" href="{{ route('admin.reports.trial-balance') }}">
                                    <i class="bi bi-journal-check"></i>
                                    <span>Trial Balance</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.profit-loss') ? 'active' : '' }}" href="{{ route('admin.reports.profit-loss') }}">
                                    <i class="bi bi-graph-up"></i>
                                    <span>Profit & Loss</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.receipt-payment') ? 'active' : '' }}" href="{{ route('admin.reports.receipt-payment') }}">
                                    <i class="bi bi-cash-coin"></i>
                                    <span>Receipt & Payment</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.balance-sheet') ? 'active' : '' }}" href="{{ route('admin.reports.balance-sheet') }}">
                                    <i class="bi bi-file-earmark-bar-graph"></i>
                                    <span>Balance Sheet</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.debtors-outstanding') ? 'active' : '' }}" href="{{ route('admin.reports.debtors-outstanding') }}">
                                    <i class="bi bi-people"></i>
                                    <span>Receivables</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.reports.creditors-outstanding') ? 'active' : '' }}" href="{{ route('admin.reports.creditors-outstanding') }}">
                                    <i class="bi bi-people-fill"></i>
                                    <span>Payables</span>
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

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.support-tickets.*') ? 'active' : '' }}" href="{{ route('admin.support-tickets.index') }}">
                        <i class="bi bi-headset"></i>
                        <span>Help & Support</span>
                    </a>
                </li>

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
                            <button type="button" id="darkModeToggle" class="btn btn-link"
                                title="{{ $darkMode === '1' ? 'Switch to light mode' : 'Switch to dark mode' }}"
                                aria-label="{{ $darkMode === '1' ? 'Switch to light mode' : 'Switch to dark mode' }}"
                                aria-pressed="{{ $darkMode === '1' ? 'true' : 'false' }}">
                                <i class="bi {{ $darkMode === '1' ? 'bi-sun' : 'bi-moon' }}"></i>
                            </button>

                            <!-- Notifications -->
                            <div class="dropdown d-inline-block" id="notificationDropdownWrap"
                                 data-feed-url="{{ route('admin.notifications.feed') }}"
                                 data-unread-url="{{ route('admin.notifications.unread-count') }}"
                                 data-read-url="{{ url('admin/notifications') }}">
                                <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown" aria-label="Notifications" id="notificationDropdownBtn">
                                    <i class="bi bi-bell"></i>
                                    <span id="notificationBadge"
                                          class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ ($headerUnreadCount ?? 0) > 0 ? '' : 'd-none' }}">
                                        {{ ($headerUnreadCount ?? 0) > 9 ? '9+' : ($headerUnreadCount ?? 0) }}
                                    </span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                                    <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                        <span>Notifications</span>
                                        <span class="badge bg-danger-subtle text-danger-emphasis d-none" id="notificationDropdownUnreadLabel"></span>
                                    </h6>
                                    <div id="notificationDropdownList">
                                        @forelse(($headerNotifications ?? []) as $notification)
                                        @php
                                            $notifUrl = app(\App\Services\NotificationService::class)->resolveUrl($notification);
                                        @endphp
                                        <a class="dropdown-item notification-item {{ $notification->is_read ? '' : 'fw-semibold unread' }}"
                                           href="{{ $notifUrl }}"
                                           data-id="{{ $notification->id }}"
                                           data-read="{{ $notification->is_read ? '1' : '0' }}">
                                            <i class="bi {{ $notification->icon ?? 'bi-bell' }} {{ $notification->color ?? 'text-primary' }}"></i>
                                            <span>{{ $notification->title }}</span>
                                        </a>
                                        @empty
                                        <span class="dropdown-item text-muted small notification-empty">No notifications</span>
                                        @endforelse
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-center" href="{{ route('admin.notifications.index') }}">View all notifications</a>
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
                                    <a class="dropdown-item" href="{{ route('admin.profile') }}">
                                        <i class="bi bi-person"></i>
                                        <span>My Profile</span>
                                    </a>
                                    <a class="dropdown-item" href="{{ $isSuperAdmin ? route('admin.subscription-plans.index') : route('admin.settings.index') }}">
                                        <i class="bi bi-gear"></i>
                                        <span>{{ $isSuperAdmin ? 'Platform Settings' : 'Settings' }}</span>
                                    </a>
                                    <a class="dropdown-item" href="{{ route('admin.profile') }}#change-password">
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

    @permission('parties.create')
    <div class="modal fade" id="partyQuickAddModal" tabindex="-1" aria-hidden="true" data-store-url="{{ route('admin.parties.store') }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title party-quick-add-title mb-0">Quick Add Party</h5>
                        <small class="text-muted">Create a customer or supplier without leaving the current form.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="partyQuickAddForm">
                        @csrf
                        <input type="hidden" name="party_target" value="">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Party Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Party Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" required>
                                    <option value="debtor">Customer</option>
                                    <option value="creditor">Supplier</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" name="mobile">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">GSTIN</label>
                                <input type="text" class="form-control" name="gstin">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="address" rows="2" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select class="form-select" name="state_id" required>
                                    <option value="">Loading states...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select class="form-select" name="city_id" disabled required>
                                    <option value="">Select City</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pincode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="postal_code" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Opening Balance</label>
                                <input type="number" class="form-control" name="opening_balance" value="0" min="0" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Balance Type</label>
                                <select class="form-select" name="opening_balance_type">
                                    <option value="debit">Debit (DR)</option>
                                    <option value="credit">Credit (CR)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Remarks</label>
                                <input type="text" class="form-control" name="remarks">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="partyQuickAddForm" class="btn btn-primary party-quick-add-submit">Create Party</button>
                </div>
            </div>
        </div>
    </div>
    @endpermission

    @permission('accounts.create')
    <div class="modal fade" id="accountQuickAddModal" tabindex="-1" aria-hidden="true" data-store-url="{{ route('admin.accounts.store') }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Quick Add Cash / Bank Ledger</h5>
                        <small class="text-muted">Create a Cash/Bank/OD ledger without leaving this form.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="accountQuickAddForm">
                        @csrf
                        <input type="hidden" name="account_target" value="">
                        <input type="hidden" name="account_type" value="asset">
                        <input type="hidden" name="duplicate_action" value="">
                        <input type="hidden" name="is_active" value="1">

                        <div class="mb-3">
                            <label class="form-label">Ledger Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="account_name" required placeholder="e.g., Cash Counter, HDFC Bank, OD Account">
                        </div>
                        <div class="mb-3">
                            <div class="form-text">
                                <i class="bi bi-calendar-event me-1"></i>Opening date is auto-set to current financial year start date.
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Opening Balance</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" name="opening_balance" value="0" min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Balance Type</label>
                                <select class="form-select" name="balance_type">
                                    <option value="debit" selected>Debit</option>
                                    <option value="credit">Credit</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="account_quick_is_cash_bank_od" checked>
                            <label class="form-check-label" for="account_quick_is_cash_bank_od">Is Cash / Bank / OD?</label>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Remarks</label>
                            <input type="text" class="form-control" name="remarks" maxlength="500" placeholder="Optional note">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="accountQuickAddForm" class="btn btn-primary">Create Ledger</button>
                </div>
            </div>
        </div>
    </div>
    @endpermission

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
        $(function() {
            function applySelectValue($select, value) {
                $select.val(value);
                $select.trigger('change');

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.trigger('change.select2');
                }
            }

            function getSelectValueMode($select) {
                return ($select.data('quick-add-value-mode') || '').toString().trim().toLowerCase();
            }

            function tokenAwareValue($select, type, id) {
                if (getSelectValueMode($select) !== 'token') {
                    return String(id);
                }

                return `${type}:${id}`;
            }

            function appendPartyOption($select, party) {
                const value = tokenAwareValue($select, 'party', party.id);
                const label = `${party.name} (${party.party_code})`;

                if ($select.find(`option[value="${value}"]`).length) {
                    return value;
                }

                const $optgroup = $select.find('optgroup[label="Cash / Bank / OD Ledgers"]').first();
                const $option = $('<option>', { value, text: label });

                if ($optgroup.length) {
                    $optgroup.before($option);
                } else {
                    $select.append($option);
                }

                return value;
            }

            function appendLedgerOption($select, account) {
                const value = tokenAwareValue($select, 'account', account.id);
                const label = `${account.account_name} (${account.account_code})`;

                if ($select.find(`option[value="${value}"]`).length) {
                    return value;
                }

                let $optgroup = $select.find('optgroup[label="Cash / Bank / OD Ledgers"]').first();
                if (!$optgroup.length) {
                    $optgroup = $('<optgroup>', { label: 'Cash / Bank / OD Ledgers' }).appendTo($select);
                }

                $optgroup.append($('<option>', { value, text: label }));

                return value;
            }

            function getQuickAddTargetSelector($select) {
                const configured = ($select.data('quick-add-target') || '').toString().trim();
                if (configured) {
                    return configured;
                }

                const selectId = ($select.attr('id') || '').toString().trim();
                return selectId ? `#${selectId}` : '';
            }

            $(document).on('change', 'select[data-quick-add-in-select="1"]', function() {
                const $select = $(this);
                const value = ($select.val() || '').toString();

                if (value !== '__quick_add_party' && value !== '__quick_add_ledger') {
                    return;
                }

                const targetSelector = getQuickAddTargetSelector($select);
                applySelectValue($select, '');

                if (value === '__quick_add_party') {
                    if (!$partyForm.length) {
                        toastr.warning('Quick Add Party is not available for your role.');
                        return;
                    }

                    const partyType = ($select.data('quick-add-party-type') || 'debtor').toString();
                    const $proxyButton = $('<button>', {
                        type: 'button',
                        class: 'd-none quick-add-party-btn',
                        'data-party-quick-add-target': targetSelector,
                        'data-party-quick-add-type': partyType
                    }).appendTo('body');

                    $proxyButton.trigger('click');
                    $proxyButton.remove();
                    return;
                }

                if (!$accountForm.length) {
                    toastr.warning('Quick Add Cash / Bank Ledger is not available for your role.');
                    return;
                }

                const $proxyButton = $('<button>', {
                    type: 'button',
                    class: 'd-none quick-add-ledger-btn',
                    'data-account-quick-add-target': targetSelector
                }).appendTo('body');

                $proxyButton.trigger('click');
                $proxyButton.remove();
            });

            const $partyModal = $('#partyQuickAddModal');
            const partyModalEl = $partyModal.get(0);
            const partyModal = partyModalEl ? bootstrap.Modal.getOrCreateInstance(partyModalEl) : null;
            const $partyForm = $('#partyQuickAddForm');
            let partyTargetSelector = '';
            let statesLoaded = false;
            let currentCountryId = null;

            function loadPartyStates(resetCities) {
                if (!$partyForm.length) {
                    return;
                }

                const $state = $partyForm.find('[name="state_id"]');
                const $city = $partyForm.find('[name="city_id"]');

                const loadStates = function(countryId) {
                    currentCountryId = countryId;
                    return $.get(`/api/locations/${countryId}/states`).done(function(states) {
                        $state.empty().append('<option value="">Select State</option>');
                        (states || []).forEach(function(state) {
                            $state.append(new Option(state.name, state.id));
                        });

                        if (resetCities) {
                            $city.empty().append('<option value="">Select City</option>').prop('disabled', true);
                        }

                        statesLoaded = true;
                    });
                };

                if (currentCountryId) {
                    loadStates(currentCountryId);
                    return;
                }

                $.get('/api/locations/countries').done(function(countries) {
                    const fallback = Array.isArray(countries) && countries.length ? countries[0].id : 101;
                    loadStates(fallback);
                }).fail(function() {
                    loadStates(101);
                });
            }

            if ($partyForm.length) {
                $(document).on('click', '.quick-add-party-btn', function() {
                    partyTargetSelector = $(this).data('party-quick-add-target') || '';
                    const partyType = ($(this).data('party-quick-add-type') || 'debtor').toString();
                    const label = partyType === 'creditor' ? 'Supplier' : 'Customer';

                    $partyForm[0].reset();
                    clearValidationErrors('#partyQuickAddForm');
                    $partyForm.find('[name="party_target"]').val(partyTargetSelector);
                    $partyForm.find('[name="type"]').val(partyType);
                    $partyForm.find('[name="opening_balance"]').val('0');
                    $partyForm.find('[name="opening_balance_type"]').val(partyType === 'creditor' ? 'credit' : 'debit');
                    $partyModal.find('.party-quick-add-title').text(`Quick Add ${label}`);

                    if (!statesLoaded) {
                        loadPartyStates(true);
                    } else {
                        $partyForm.find('[name="city_id"]').empty().append('<option value="">Select City</option>').prop('disabled', true);
                    }

                    if (partyModal) {
                        partyModal.show();
                    }
                });

                $partyForm.on('change', '[name="state_id"]', function() {
                    const stateId = $(this).val();
                    const $city = $partyForm.find('[name="city_id"]');

                    $city.empty().append('<option value="">Loading cities...</option>').prop('disabled', true);

                    if (!stateId) {
                        $city.empty().append('<option value="">Select City</option>').prop('disabled', true);
                        return;
                    }

                    $.get(`/api/locations/${stateId}/cities`).done(function(cities) {
                        $city.empty().append('<option value="">Select City</option>');
                        (cities || []).forEach(function(city) {
                            $city.append(new Option(city.name, city.id));
                        });
                        $city.prop('disabled', false);
                    }).fail(function() {
                        $city.empty().append('<option value="">Select City</option>').prop('disabled', true);
                        toastr.error('Unable to load cities.');
                    });
                });

                ajaxFormSubmit(
                    '#partyQuickAddForm',
                    $partyModal.data('store-url'),
                    'POST',
                    function(response) {
                        const party = response.data;
                        const target = partyTargetSelector || $partyForm.find('[name="party_target"]').val();
                        const $target = target ? $(target) : $();

                        if (!$target.length) {
                            toastr.warning('Party created but target dropdown was not found.');
                            if (partyModal) {
                                partyModal.hide();
                            }
                            return;
                        }

                        const value = appendPartyOption($target, party);
                        applySelectValue($target, value);

                        if (partyModal) {
                            partyModal.hide();
                        }
                    },
                    function(xhr) {
                        const response = xhr.responseJSON;

                        if (xhr.status !== 409 || response?.code !== 'SOFT_DELETED_PARTY_EXISTS') {
                            return false;
                        }

                        Swal.fire({
                            title: 'Deleted Party Found',
                            text: `${response.data.party_name} (${response.data.party_code}) exists in deleted records.`,
                            icon: 'question',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: 'Restore Party',
                            denyButtonText: 'Create New Entry',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#16a34a',
                            denyButtonColor: '#4f46e5',
                            cancelButtonColor: '#6b7280'
                        }).then(function(result) {
                            if (!result.isConfirmed && !result.isDenied) {
                                return;
                            }

                            $partyForm.find('[name="duplicate_action"]').remove();
                            $('<input>', {
                                type: 'hidden',
                                name: 'duplicate_action',
                                value: result.isConfirmed ? 'restore' : 'new_entry'
                            }).appendTo($partyForm);

                            $partyForm.trigger('submit');
                        });

                        return true;
                    }
                );
            }

            const $accountModal = $('#accountQuickAddModal');
            const accountModalEl = $accountModal.get(0);
            const accountModal = accountModalEl ? bootstrap.Modal.getOrCreateInstance(accountModalEl) : null;
            const $accountForm = $('#accountQuickAddForm');
            let accountTargetSelector = '';

            if ($accountForm.length) {
                $(document).on('click', '.quick-add-ledger-btn', function() {
                    accountTargetSelector = $(this).data('account-quick-add-target') || '';

                    $accountForm[0].reset();
                    clearValidationErrors('#accountQuickAddForm');
                    $accountForm.find('[name="account_target"]').val(accountTargetSelector);
                    $accountForm.find('[name="opening_balance"]').val('0');
                    $accountForm.find('[name="balance_type"]').val('debit');
                    $accountForm.find('[name="duplicate_action"]').val('');
                    $('#account_quick_is_cash_bank_od').prop('checked', true);

                    if (accountModal) {
                        accountModal.show();
                    }
                });

                ajaxFormSubmit(
                    '#accountQuickAddForm',
                    $accountModal.data('store-url'),
                    'POST',
                    function(response) {
                        const account = response.data;
                        const target = accountTargetSelector || $accountForm.find('[name="account_target"]').val();
                        const $target = target ? $(target) : $();

                        if (!$target.length) {
                            toastr.warning('Ledger created but target dropdown was not found.');
                            if (accountModal) {
                                accountModal.hide();
                            }
                            return;
                        }

                        const value = appendLedgerOption($target, account);
                        applySelectValue($target, value);

                        if (accountModal) {
                            accountModal.hide();
                        }
                    },
                    function(xhr) {
                        const response = xhr.responseJSON;

                        if (xhr.status !== 409 || response?.code !== 'SOFT_DELETED_ACCOUNT_EXISTS') {
                            return false;
                        }

                        Swal.fire({
                            title: 'Deleted Account Found',
                            text: `${response.data.account_name} (${response.data.account_code}) exists in deleted records.`,
                            icon: 'question',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: 'Restore Account',
                            denyButtonText: 'Create New Entry',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#16a34a',
                            denyButtonColor: '#4f46e5',
                            cancelButtonColor: '#6b7280'
                        }).then(function(result) {
                            if (!result.isConfirmed && !result.isDenied) {
                                return;
                            }

                            $accountForm.find('[name="duplicate_action"]').val(result.isConfirmed ? 'restore' : 'new_entry');
                            $accountForm.trigger('submit');
                        });

                        return true;
                    }
                );

                $('#account_quick_is_cash_bank_od').on('change', function() {
                    const enabled = $(this).is(':checked');
                    let $hidden = $accountForm.find('input[name="is_cash_bank_od"]');
                    if (!$hidden.length) {
                        $hidden = $('<input>', { type: 'hidden', name: 'is_cash_bank_od' }).appendTo($accountForm);
                    }
                    $hidden.val(enabled ? '1' : '0');
                }).trigger('change');
            }
        });
    </script>

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
            $('#darkModeToggle').on('click', function() {
                var $toggle = $(this);
                var $icon = $toggle.find('i');
                var isDark = $('body').hasClass('dark-mode');
                var newMode = isDark ? '0' : '1';

                $('body').toggleClass('dark-mode');
                $icon.toggleClass('bi-moon bi-sun');
                $toggle
                    .prop('disabled', true)
                    .attr('aria-pressed', newMode === '1' ? 'true' : 'false')
                    .attr('aria-label', newMode === '1' ? 'Switch to light mode' : 'Switch to dark mode')
                    .attr('title', newMode === '1' ? 'Switch to light mode' : 'Switch to dark mode');

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
                    },
                    error: function(xhr) {
                        $('body').toggleClass('dark-mode');
                        $icon.toggleClass('bi-moon bi-sun');
                        $toggle
                            .attr('aria-pressed', isDark ? 'true' : 'false')
                            .attr('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode')
                            .attr('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');

                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Unable to update theme.';
                        toastr.error(message);
                    },
                    complete: function() {
                        $toggle.prop('disabled', false);
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

        const $notifWrap = $('#notificationDropdownWrap');
        if (!$notifWrap.length) {
            return;
        }

        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const feedUrl = $notifWrap.data('feed-url');
        const unreadUrl = $notifWrap.data('unread-url');
        const readBaseUrl = $notifWrap.data('read-url');

        function updateNotificationBadge(count) {
            const $badge = $('#notificationBadge');
            const $label = $('#notificationDropdownUnreadLabel');
            const safeCount = Math.max(0, parseInt(count, 10) || 0);

            if (safeCount > 0) {
                $badge.removeClass('d-none').text(safeCount > 9 ? '9+' : safeCount);
                $label.removeClass('d-none').text(safeCount + ' unread');
            } else {
                $badge.addClass('d-none').text('0');
                $label.addClass('d-none').text('');
            }

            window.recoNotificationUnreadCount = safeCount;
            $(document).trigger('reco:notifications-updated', [safeCount]);
        }

        function renderNotificationList(notifications) {
            const $list = $('#notificationDropdownList');
            $list.empty();

            if (!notifications || !notifications.length) {
                $list.append('<span class="dropdown-item text-muted small notification-empty">No notifications</span>');
                return;
            }

            notifications.forEach(function (item) {
                const unreadClass = item.is_read ? '' : ' fw-semibold unread';
                $list.append(
                    '<a class="dropdown-item notification-item' + unreadClass + '" href="' + item.url + '" data-id="' + item.id + '" data-read="' + (item.is_read ? '1' : '0') + '">' +
                        '<i class="bi ' + item.icon + ' ' + item.color + '"></i>' +
                        '<span>' + $('<div>').text(item.title).html() + '</span>' +
                    '</a>'
                );
            });
        }

        function refreshNotifications() {
            return $.get(feedUrl).done(function (data) {
                updateNotificationBadge(data.unread_count);
                renderNotificationList(data.notifications);
            });
        }

        function refreshUnreadCountOnly() {
            return $.get(unreadUrl).done(function (data) {
                updateNotificationBadge(data.unread_count);
            });
        }

        window.recoRefreshNotifications = refreshNotifications;
        window.recoUpdateNotificationBadge = updateNotificationBadge;

        $('#notificationDropdownBtn').on('show.bs.dropdown', function () {
            refreshNotifications();
        });

        $(document).on('click', '.notification-item', function (e) {
            const $item = $(this);
            const id = $item.data('id');
            const isRead = String($item.data('read')) === '1';
            const targetUrl = $item.attr('href');

            if (isRead || !id) {
                return;
            }

            e.preventDefault();

            $.ajax({
                url: readBaseUrl + '/' + id + '/read',
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken },
            }).always(function () {
                window.location.href = targetUrl;
            });
        });

        refreshUnreadCountOnly();
        setInterval(refreshUnreadCountOnly, 60000);
    });
    </script>
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>
