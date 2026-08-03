<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger Report</title>
    <style>
        @page { margin: 10px 12px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 11px;
        }
        .container { padding: 0; }
        .header { margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; }
        .meta { margin-top: 6px; color: #4b5563; line-height: 1.45; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .table th, .table td {
            border: 1px solid #d6dbe6;
            padding: 6px;
        }
        .table th {
            background: #f3f6fc;
            text-align: left;
            font-weight: 700;
        }
        .text-right { text-align: right; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Ledger Report</h1>
        <div class="meta">
            <strong>Account:</strong> {{ $report['account']->account_code }} - {{ $report['account']->account_name }}<br>
            <strong>Opening Balance:</strong> ₹{{ number_format($report['opening_balance']['balance'], 2) }} @drCr($report['opening_balance']['type'])<br>
            <strong>Closing Balance:</strong> ₹{{ number_format($report['closing_balance']['balance'], 2) }} @drCr($report['closing_balance']['type'])
        </div>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Voucher</th>
            <th>Description</th>
            <th class="text-right">Debit (₹)</th>
            <th class="text-right">Credit (₹)</th>
            <th class="text-right">Balance (₹)</th>
        </tr>
        </thead>
        <tbody>
        @forelse($report['entries'] as $entry)
            <tr>
                <td>@istDate($entry->transaction_date)</td>
                <td>{{ $entry->voucher?->voucher_number ?? '-' }}</td>
                <td>{{ $entry->voucher?->narration ?? '-' }}</td>
                <td class="text-right">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '-' }}</td>
                <td class="text-right">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '-' }}</td>
                <td class="text-right">{{ number_format(abs($entry->running_balance), 2) }} @drCr($entry->balance_type)</td>
            </tr>
        @empty
            <tr><td colspan="6">No entries found</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
