<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Day Book Report</title>
    <style>
        @page { margin: 10px 10px; }
        body { font-family: DejaVu Sans, sans-serif; color: #333; margin: 0; padding: 0; font-size: 11px; }
        .container { padding: 0; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 4px 0 0; color: #6b7280; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table th, .table td { border: 1px solid #d6dbe6; padding: 6px 5px; }
        .table th { background: #f3f6fc; text-align: left; font-weight: 700; }
        .text-right { text-align: right; }
        tfoot td { font-weight: bold; background: #eef2f8; }
        .table th:nth-child(2),
        .table th:nth-child(3),
        .table th:nth-child(4),
        .table td:nth-child(2),
        .table td:nth-child(3),
        .table td:nth-child(4) { white-space: nowrap; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Day Book</h1>
        <p>Period: @istDate($dateFrom) to @istDate($dateTo)</p>
    </div>

    @include('exports._meta')

    <table class="table">
        <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Voucher #</th>
            <th>Type</th>
            <th>Particulars</th>
            <th>Narration</th>
            <th class="text-right">Debit (₹)</th>
            <th class="text-right">Credit (₹)</th>
        </tr>
        </thead>
        <tbody>
        @forelse($report['rows'] as $row)
            <tr>
                <td>{{ $row['serial'] ?? $loop->iteration }}</td>
                <td>@istDate($row['voucher_date'] ?? null)</td>
                <td>{{ $row['voucher_number'] }}</td>
                <td>{{ ucfirst($row['voucher_type']) }}</td>
                <td>{{ $row['account_name'] }}</td>
                <td>{{ $row['narration'] ?? '-' }}</td>
                <td class="text-right">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}</td>
                <td class="text-right">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No entries found for selected period</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">Total</td>
                <td class="text-right">₹{{ number_format($report['total_debit'], 2) }}</td>
                <td class="text-right">₹{{ number_format($report['total_credit'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
