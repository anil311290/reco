<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Day Book Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .container { padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 22px; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .table th {
            background: #f7f7f7;
            text-align: left;
        }
        .text-right { text-align: right; }
        tfoot td { font-weight: bold; background: #f3f3f3; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Day Book</h1>
        <p>Date: {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</p>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>Voucher #</th>
            <th>Type</th>
            <th>Party</th>
            <th>Narration</th>
            <th class="text-right">Debit (₹)</th>
            <th class="text-right">Credit (₹)</th>
        </tr>
        </thead>
        <tbody>
        @forelse($report['vouchers'] as $voucher)
            <tr>
                <td>{{ $voucher->voucher_number }}</td>
                <td>{{ ucfirst($voucher->voucher_type) }}</td>
                <td>{{ $voucher->party->name ?? '-' }}</td>
                <td>{{ $voucher->narration ?? '-' }}</td>
                <td class="text-right">{{ $voucher->total_debit > 0 ? number_format($voucher->total_debit, 2) : '-' }}</td>
                <td class="text-right">{{ $voucher->total_credit > 0 ? number_format($voucher->total_credit, 2) : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No entries found for selected date</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td class="text-right">₹{{ number_format($report['total_debit'], 2) }}</td>
                <td class="text-right">₹{{ number_format($report['total_credit'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
