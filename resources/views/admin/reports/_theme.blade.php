@once
    @push('styles')
        <style>
            .reports-shell {
                display: grid;
                gap: 0.9rem;
            }

            .report-hero {
                position: relative;
                overflow: visible;
                border: 1px solid rgba(31, 41, 55, 0.08);
                border-radius: 14px;
                padding: 0.85rem 1rem;
                background: #ffffff;
                box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
            }

            .report-hero::after {
                display: none;
            }

            .report-eyebrow {
                display: none;
            }

            .report-title {
                margin: 0;
                font-size: clamp(1.15rem, 1.6vw, 1.45rem);
                line-height: 1.1;
                font-weight: 700;
                color: #23263a;
            }

            .report-subtitle {
                display: none;
            }

            .report-toolbar {
                display: flex;
                flex-wrap: nowrap;
                gap: 0.65rem;
                justify-content: flex-end;
                align-items: center;
                position: relative;
                z-index: 1;
                overflow-x: auto;
                overflow-y: hidden;
                scrollbar-width: thin;
                -webkit-overflow-scrolling: touch;
            }

            .report-toolbar > .btn {
                flex: 0 0 auto;
            }

            .report-btn-soft {
                border-radius: 999px;
                border: 1px solid rgba(35, 38, 58, 0.12);
                background: rgba(255, 255, 255, 0.82);
                color: #2f3550;
                padding-inline: 1rem;
                height: 44px;
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .report-btn-soft:hover {
                background: #fff;
                border-color: rgba(115, 103, 240, 0.28);
                color: #5b53d6;
            }

            .report-btn-export {
                border-radius: 14px;
                padding: 0.58rem 1.1rem;
                font-size: 0.84rem;
                font-weight: 700;
                height: 44px;
                min-height: 44px;
                min-width: 110px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                letter-spacing: 0.01em;
                line-height: 1;
                white-space: nowrap;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
                transition: all 0.2s ease;
            }

            .report-btn-export i {
                font-size: 0.95rem;
                line-height: 1;
            }

            .report-btn-export.btn-outline-success {
                --bs-btn-color: #047857;
                --bs-btn-border-color: rgba(5, 150, 105, 0.35);
                --bs-btn-bg: rgba(16, 185, 129, 0.14);
                --bs-btn-hover-color: #ffffff;
                --bs-btn-hover-bg: #059669;
                --bs-btn-hover-border-color: #059669;
                --bs-btn-active-color: #ffffff;
                --bs-btn-active-bg: #047857;
                --bs-btn-active-border-color: #047857;
                background-color: rgba(16, 185, 129, 0.14);
            }

            .report-btn-export.btn-outline-danger {
                --bs-btn-color: #dc2626;
                --bs-btn-border-color: rgba(239, 68, 68, 0.38);
                --bs-btn-bg: rgba(248, 113, 113, 0.14);
                --bs-btn-hover-color: #ffffff;
                --bs-btn-hover-bg: #dc2626;
                --bs-btn-hover-border-color: #dc2626;
                --bs-btn-active-color: #ffffff;
                --bs-btn-active-bg: #b91c1c;
                --bs-btn-active-border-color: #b91c1c;
                background-color: rgba(248, 113, 113, 0.14);
            }

            .report-btn-export.btn-outline-success:hover,
            .report-btn-export.btn-outline-success:focus {
                color: #ffffff;
                border-color: #059669;
                background: linear-gradient(135deg, #10b981, #059669);
                transform: translateY(-1px);
                box-shadow: 0 12px 26px rgba(16, 185, 129, 0.28);
            }

            .report-btn-export.btn-outline-danger:hover,
            .report-btn-export.btn-outline-danger:focus {
                color: #ffffff;
                border-color: #dc2626;
                background: linear-gradient(135deg, #ef4444, #dc2626);
                transform: translateY(-1px);
                box-shadow: 0 12px 26px rgba(239, 68, 68, 0.28);
            }

            .report-btn-export:active {
                transform: translateY(0);
            }

            .report-filter-card,
            .report-panel,
            .report-stat,
            .report-feature-card {
                border: 1px solid rgba(31, 41, 55, 0.08);
                border-radius: 22px;
                background: #ffffff;
                box-shadow: 0 14px 40px rgba(15, 23, 42, 0.06);
            }

            .report-filter-card {
                padding: 0.85rem;
            }

            .report-filter-card .form-label {
                font-size: 0.78rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #7c8298;
                margin-bottom: 0.45rem;
            }

            .report-filter-card .form-control,
            .report-filter-card .form-select {
                min-height: 44px;
                height: 44px;
                border-radius: 14px;
                background: #fbfcff;
                border-color: rgba(31, 41, 55, 0.08);
            }

            .report-filter-card .select2-container {
                width: 100% !important;
            }

            .report-filter-card .select2-container--bootstrap-5 .select2-selection {
                min-height: 44px;
                height: 44px;
                border-radius: 14px;
                background: #fbfcff;
                border-color: rgba(31, 41, 55, 0.08);
            }

            .report-filter-card .select2-container--bootstrap-5 .select2-selection--single {
                padding: 0.58rem 2.25rem 0.58rem 0.95rem;
            }

            .report-filter-card .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
                line-height: 1.4;
                padding: 0;
            }

            .report-filter-card .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
                right: 0.75rem;
            }

            .report-filter-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.65rem;
                align-items: center;
                align-content: center;
            }

            .report-filter-actions > .btn {
                height: 44px;
                min-height: 44px;
                min-width: 110px;
                padding: 0.58rem 1.1rem;
                border-radius: 14px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                font-size: 0.84rem;
                font-weight: 700;
                line-height: 1;
                white-space: nowrap;
                margin: 0;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            }

            .report-filter-actions > .btn.btn-primary {
                padding-inline: 1.1rem;
            }

            .report-toolbar > .btn {
                height: 44px;
                min-height: 44px;
                min-width: 110px;
                padding: 0.58rem 1.1rem;
                border-radius: 14px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                font-size: 0.84rem;
                font-weight: 700;
                line-height: 1;
                white-space: nowrap;
                margin: 0;
            }

            .report-stats-grid {
                display: none;
                gap: 1rem;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                margin: 10px 0;
            }

            .report-stat {
                position: relative;
                overflow: hidden;
                padding: 1.15rem 1.2rem;
            }

            .report-stat::before {
                content: '';
                position: absolute;
                inset: 0 auto 0 0;
                width: 4px;
                background: var(--report-accent, #7367f0);
            }

            .report-stat-label {
                margin: 0;
                color: #7c8298;
                font-size: 0.8rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .report-stat-value {
                margin: 0.45rem 0 0;
                font-size: 1.65rem;
                font-weight: 800;
                color: #23263a;
                letter-spacing: -0.02em;
            }

            .report-stat-note {
                margin-top: 0.4rem;
                color: #7c8298;
                font-size: 0.88rem;
            }

            .report-stat--success { --report-accent: #16a34a; }
            .report-stat--danger { --report-accent: #dc2626; }
            .report-stat--warning { --report-accent: #d97706; }
            .report-stat--info { --report-accent: #0284c7; }
            .report-stat--primary { --report-accent: #7367f0; }

            .report-panel {
                overflow: hidden;
            }

            .report-panel-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                padding: 0.78rem 0.95rem;
                border-bottom: 1px solid rgba(31, 41, 55, 0.06);
                background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.95));
            }

            .report-panel-title {
                margin: 0;
                display: flex;
                align-items: center;
                gap: 0.6rem;
                font-size: 1rem;
                font-weight: 700;
                color: #23263a;
            }

            .report-panel-body {
                padding: 0.85rem 0.95rem;
            }

            .report-panel-body.report-panel-body--flush {
                padding: 0;
            }

            .report-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.45rem 0.8rem;
                border-radius: 999px;
                font-size: 0.78rem;
                font-weight: 700;
                background: #f4f6fb;
                color: #5f6783;
            }

            .report-pill--success { background: rgba(22, 163, 74, 0.12); color: #15803d; }
            .report-pill--danger { background: rgba(220, 38, 38, 0.12); color: #b91c1c; }
            .report-pill--warning { background: rgba(217, 119, 6, 0.14); color: #b45309; }
            .report-pill--info { background: rgba(2, 132, 199, 0.12); color: #0369a1; }

            .report-empty {
                display: grid;
                place-items: center;
                gap: 0.75rem;
                min-height: 140px;
                text-align: center;
                color: #7c8298;
            }

            .report-empty-icon {
                width: 72px;
                height: 72px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 20px;
                background: linear-gradient(135deg, rgba(115, 103, 240, 0.14), rgba(2, 132, 199, 0.12));
                color: #6559ea;
                font-size: 1.9rem;
            }

            .report-table {
                margin: 0;
                --bs-table-bg: transparent;
                --bs-table-hover-bg: rgba(115, 103, 240, 0.06);
                border-collapse: separate;
                border-spacing: 0;
            }

            .report-table thead th {
                padding: 0.88rem 1.05rem;
                font-size: 0.76rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #7c8298;
                background: #f8faff;
                border-bottom-width: 1px;
                border-top: 1px solid rgba(31, 41, 55, 0.08);
            }

            .report-table tbody td,
            .report-table tfoot td {
                padding: 0.88rem 1.05rem;
                vertical-align: middle;
                border-color: rgba(31, 41, 55, 0.06);
                font-size: 0.91rem;
            }

            .report-table tbody tr:nth-child(even) td {
                background: #fcfdff;
            }

            .report-table thead th:first-child,
            .report-table tbody td:first-child,
            .report-table tfoot td:first-child {
                border-left: 1px solid rgba(31, 41, 55, 0.08);
            }

            .report-table thead th:last-child,
            .report-table tbody td:last-child,
            .report-table tfoot td:last-child {
                border-right: 1px solid rgba(31, 41, 55, 0.08);
            }

            .report-table tbody tr:last-child td {
                border-bottom: 1px solid rgba(31, 41, 55, 0.08);
            }

            .report-table tfoot td {
                background: #23263a;
                color: #fff;
                font-weight: 700;
            }

            .report-table thead th:first-child {
                border-top-left-radius: 10px;
            }

            .report-table thead th:last-child {
                border-top-right-radius: 10px;
            }

            .report-table tfoot td:first-child {
                border-bottom-left-radius: 10px;
            }

            .report-table tfoot td:last-child {
                border-bottom-right-radius: 10px;
            }

            .report-table-tools {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                padding: 0.75rem 1rem 0.55rem;
                background: #f8faff;
                border-top: 1px solid rgba(31, 41, 55, 0.08);
                border-bottom: 1px solid rgba(31, 41, 55, 0.06);
            }

            .report-rows-form {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                margin: 0;
            }

            .report-rows-label {
                margin: 0;
                font-size: 0.75rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #6f7896;
                white-space: nowrap;
            }

            .report-rows-select {
                min-width: 88px;
                border-radius: 10px;
                border-color: rgba(31, 41, 55, 0.14);
                background: #ffffff;
            }

            .report-pagination {
                padding: 0.85rem 1rem;
                border-top: 1px solid rgba(31, 41, 55, 0.08);
                background: #f8faff;
            }

            .report-pagination nav {
                margin: 0;
            }

            .report-pagination .pagination {
                margin: 0;
                justify-content: flex-end;
                gap: 0.25rem;
            }

            .report-pagination .page-link {
                border-radius: 10px;
                border-color: rgba(31, 41, 55, 0.12);
                color: #45506e;
                min-width: 38px;
                text-align: center;
            }

            .report-pagination .page-item.active .page-link {
                background: #4f46e5;
                border-color: #4f46e5;
                color: #fff;
            }

            .report-table .report-row-emphasis td {
                background: linear-gradient(90deg, rgba(245, 158, 11, 0.12), rgba(245, 158, 11, 0.05));
            }

            .report-detail-link {
                color: #4f46e5;
                font-weight: 600;
                text-decoration: none;
                border-bottom: 1px dashed rgba(79, 70, 229, 0.35);
            }

            .report-detail-link:hover {
                color: #3730a3;
                border-bottom-color: #3730a3;
            }

            body.dark-mode .report-detail-link {
                color: #a5b4fc;
                border-bottom-color: rgba(165, 180, 252, 0.4);
            }

            body.dark-mode .report-detail-link:hover {
                color: #c7d2fe;
                border-bottom-color: #c7d2fe;
            }

            .report-feature-grid {
                display: grid;
                gap: 0.9rem;
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .report-feature-card {
                height: 100%;
                padding: 1rem;
                border-radius: 14px;
                transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
                display: flex;
                flex-direction: column;
            }

            .report-feature-card:hover {
                transform: translateY(-2px);
                border-color: rgba(115, 103, 240, 0.18);
                box-shadow: 0 10px 26px rgba(15, 23, 42, 0.1);
            }

            .report-feature-icon {
                width: 44px;
                height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                margin-bottom: 0.75rem;
                font-size: 1.15rem;
                color: #fff;
                background: linear-gradient(135deg, var(--report-icon-start, #7367f0), var(--report-icon-end, #60a5fa));
                box-shadow: 0 8px 18px rgba(115, 103, 240, 0.2);
            }

            .report-feature-title {
                margin-bottom: 0.45rem;
                font-size: 0.98rem;
                font-weight: 700;
                color: #23263a;
            }

            .report-feature-text {
                margin-bottom: 0.8rem;
                color: #70758d;
                line-height: 1.45;
                font-size: 0.86rem;
                flex-grow: 1;
            }

            .report-feature-card .btn {
                width: 100%;
                border-radius: 10px;
                font-size: 0.82rem;
                font-weight: 700;
            }

            @media (max-width: 1199.98px) {
                .report-feature-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (max-width: 991.98px) {
                .report-feature-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 575.98px) {
                .report-feature-grid {
                    grid-template-columns: 1fr;
                }
            }

            .report-kpi-bar {
                display: none;
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            .report-kpi-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.8rem 1rem;
                border-radius: 18px;
                background: #f8faff;
                color: #39405a;
                font-weight: 600;
            }

            .report-kpi-chip i {
                color: #7367f0;
            }

            body.dark-mode .report-hero {
                background: linear-gradient(135deg, #111827 0%, #1d2338 45%, #111827 100%);
                border-color: rgba(148, 163, 184, 0.15);
                box-shadow: 0 20px 45px rgba(0, 0, 0, 0.32);
            }

            body.dark-mode .report-hero::after {
                background: rgba(115, 103, 240, 0.12);
            }

            body.dark-mode .report-eyebrow {
                background: rgba(148, 163, 184, 0.12);
                color: #cbd5e1;
            }

            body.dark-mode .report-title,
            body.dark-mode .report-subtitle,
            body.dark-mode .report-panel-title,
            body.dark-mode .report-stat-label,
            body.dark-mode .report-stat-value,
            body.dark-mode .report-pill,
            body.dark-mode .report-kpi-chip,
            body.dark-mode .report-empty,
            body.dark-mode .report-empty-icon {
                color: #e2e8f0;
            }

            body.dark-mode .report-subtitle,
            body.dark-mode .report-note,
            body.dark-mode .report-feature-text,
            body.dark-mode .report-stat-note {
                color: #aeb8d2;
            }

            body.dark-mode .report-stat-value {
                color: #f8fafc;
            }

            body.dark-mode .report-btn-soft {
                border-color: rgba(148, 163, 184, 0.18);
                background: rgba(255, 255, 255, 0.06);
                color: #e2e8f0;
            }

            body.dark-mode .report-btn-export.btn-outline-success,
            body.dark-mode .report-btn-export.btn-outline-secondary,
            body.dark-mode .report-btn-export.btn-outline-primary {
                border-color: rgba(148, 163, 184, 0.25);
                color: #e2e8f0;
                background: rgba(255, 255, 255, 0.05);
                box-shadow: 0 10px 26px rgba(2, 6, 23, 0.35);
            }

            body.dark-mode .report-btn-export.btn-outline-success {
                --bs-btn-color: #6ee7b7;
                --bs-btn-border-color: rgba(52, 211, 153, 0.45);
                --bs-btn-bg: rgba(16, 185, 129, 0.16);
                --bs-btn-hover-color: #052e23;
                --bs-btn-hover-bg: #6ee7b7;
                --bs-btn-hover-border-color: #6ee7b7;
                --bs-btn-active-color: #052e23;
                --bs-btn-active-bg: #34d399;
                --bs-btn-active-border-color: #34d399;
            }

            body.dark-mode .report-btn-export.btn-outline-danger {
                --bs-btn-color: #fca5a5;
                --bs-btn-border-color: rgba(248, 113, 113, 0.45);
                --bs-btn-bg: rgba(239, 68, 68, 0.16);
                --bs-btn-hover-color: #3f0b0b;
                --bs-btn-hover-bg: #fca5a5;
                --bs-btn-hover-border-color: #fca5a5;
                --bs-btn-active-color: #3f0b0b;
                --bs-btn-active-bg: #f87171;
                --bs-btn-active-border-color: #f87171;
            }

            body.dark-mode .report-btn-export.btn-outline-success:hover,
            body.dark-mode .report-btn-export.btn-outline-danger:hover {
                color: #ffffff;
            }

            body.dark-mode .report-filter-card,
            body.dark-mode .report-panel,
            body.dark-mode .report-stat,
            body.dark-mode .report-feature-card,
            body.dark-mode .report-kpi-chip {
                background: #161b2a;
                border-color: rgba(148, 163, 184, 0.16);
                box-shadow: 0 14px 40px rgba(0, 0, 0, 0.18);
            }

            body.dark-mode .report-panel-header {
                background: rgba(255, 255, 255, 0.04);
                border-color: rgba(148, 163, 184, 0.12);
            }

            body.dark-mode .report-pill {
                background: rgba(148, 163, 184, 0.08);
                color: #dbe4ff;
            }

            body.dark-mode .report-pill--success {
                background: rgba(16, 185, 129, 0.14);
                color: #a7f3d0;
            }

            body.dark-mode .report-pill--danger {
                background: rgba(239, 68, 68, 0.14);
                color: #fecaca;
            }

            body.dark-mode .report-pill--warning {
                background: rgba(245, 158, 11, 0.14);
                color: #fde68a;
            }

            body.dark-mode .report-pill--info {
                background: rgba(56, 189, 248, 0.14);
                color: #bae6fd;
            }

            body.dark-mode .report-table thead th {
                background: #0f172a;
                color: #cbd5e1;
                border-color: rgba(148, 163, 184, 0.12);
            }

            body.dark-mode .report-table tbody td,
            body.dark-mode .report-table tfoot td {
                background: #111827;
                color: #e2e8f0;
                border-color: rgba(148, 163, 184, 0.08);
            }

            body.dark-mode .report-table tbody tr:nth-child(even) td {
                background: #0d1527;
            }

            body.dark-mode .report-table tfoot td {
                background: #0b1121;
                color: #f8fafc;
            }

            body.dark-mode .report-table-tools {
                background: #0f172a;
                border-top-color: rgba(148, 163, 184, 0.12);
                border-bottom-color: rgba(148, 163, 184, 0.1);
            }

            body.dark-mode .report-rows-label {
                color: #cbd5e1;
            }

            body.dark-mode .report-rows-select {
                background: #111827;
                color: #e2e8f0;
                border-color: rgba(148, 163, 184, 0.2);
            }

            body.dark-mode .report-pagination {
                background: #0f172a;
                border-color: rgba(148, 163, 184, 0.12);
            }

            body.dark-mode .report-pagination .page-link {
                background: #111827;
                border-color: rgba(148, 163, 184, 0.16);
                color: #dbe4ff;
            }

            body.dark-mode .report-pagination .page-item.disabled .page-link {
                background: #0b1222;
                color: #6b7280;
            }

            body.dark-mode .report-row-emphasis td {
                background: rgba(255, 255, 255, 0.04);
            }

            body.dark-mode .report-empty {
                background: #111827;
                border-color: rgba(148, 163, 184, 0.12);
            }

            body.dark-mode .report-empty-icon {
                background: linear-gradient(135deg, rgba(115, 103, 240, 0.18), rgba(56, 189, 248, 0.14));
                color: #eef2ff;
            }

            body.dark-mode .report-feature-icon {
                box-shadow: 0 14px 30px rgba(0, 0, 0, 0.24);
            }

            body.dark-mode .report-filter-card .form-control,
            body.dark-mode .report-filter-card .form-select {
                background: #111827;
                color: #e2e8f0;
                border-color: rgba(148, 163, 184, 0.16);
            }

            body.dark-mode .report-filter-card .form-label {
                color: #cbd5e1;
            }

            body.dark-mode .report-feature-card {
                border-color: rgba(148, 163, 184, 0.12);
            }

            @media (max-width: 767.98px) {
                .report-hero {
                    padding: 1.2rem;
                    border-radius: 20px;
                }

                .report-toolbar {
                    justify-content: flex-start;
                    margin-top: 1rem;
                }

                .report-filter-actions > .btn,
                .report-toolbar > .btn,
                .report-btn-export {
                    min-width: 100px;
                    flex: 0 0 auto;
                }

                .report-panel-header {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .report-table thead th,
                .report-table tbody td,
                .report-table tfoot td {
                    padding: 0.8rem;
                }
            }
        </style>
    @endpush
@endonce
