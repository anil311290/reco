<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
        }
        .header {
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0 0 6px;
            font-size: 20px;
        }
        .meta {
            margin: 2px 0;
            color: #4b5563;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f3f4f6;
        }
        .text-right {
            text-align: right;
        }
        .total-row td {
            background: #e5e7eb;
            font-weight: 700;
        }
        .muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    @php
        $selectedAccount = $report['account'] ?? null;
        $ledger = $report['report'] ?? null;
        $entries = $ledger['entries'] ?? collect();
        $opening = $ledger['opening_balance'] ?? ['balance' => 0, 'type' => '-'];
        $closing = $ledger['closing_balance'] ?? ['balance' => 0, 'type' => '-'];
        $totalDebit = $ledger['total_debit'] ?? 0;
        $totalCredit = $ledger['total_credit'] ?? 0;
    @endphp

    <div class="header">
        <h1>{{ $title }}</h1>
        @if(!empty($selectedAccount))
            <div class="meta">
                Account: {{ $selectedAccount['account_code'] ?? '' }} {{ $selectedAccount['account_name'] ?? '' }}
            </div>
        @endif
        @if(!empty($report['message']))
            <div class="meta">{{ $report['message'] }}</div>
        @endif
        <div class="meta">
            Opening: {{ number_format((float) ($opening['balance'] ?? 0), 2) }} {{ strtoupper((string) ($opening['type'] ?? '')) }}
        </div>
        <div class="meta">
            Closing: {{ number_format((float) ($closing['balance'] ?? 0), 2) }} {{ strtoupper((string) ($closing['type'] ?? '')) }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Voucher</th>
                <th>Particulars</th>
                <th class="text-right">Receipts</th>
                <th class="text-right">Payments</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry->transaction_date)->format('d-m-Y') }}</td>
                    <td>{{ $entry->voucher->voucher_number ?? '-' }}</td>
                    <td>{{ $entry->particulars ?? ($entry->description ?: ($entry->voucher->narration ?? '-')) }}</td>
                    <td class="text-right">{{ number_format((float) $entry->debit, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $entry->credit, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $entry->running_balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">No entries available.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3">Totals</td>
                <td class="text-right">{{ number_format((float) $totalDebit, 2) }}</td>
                <td class="text-right">{{ number_format((float) $totalCredit, 2) }}</td>
                <td class="text-right">{{ number_format((float) ($closing['balance'] ?? 0), 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
