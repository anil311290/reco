<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Day Book Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #333; margin: 0; padding: 0; font-size: 12px; }
        .container { padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 22px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
        .table th { background: #f7f7f7; text-align: left; }
        .text-right { text-align: right; }
        tfoot td { font-weight: bold; background: #f3f3f3; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Day Book</h1>
        <p>Date: @istDate($date)</p>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>#</th>
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
                <td>{{ $row['voucher_number'] }}</td>
                <td>{{ ucfirst($row['voucher_type']) }}</td>
                <td>{{ $row['account_name'] }}</td>
                <td>{{ $row['narration'] ?? '-' }}</td>
                <td class="text-right">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}</td>
                <td class="text-right">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No entries found for selected date</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total</td>
                <td class="text-right">₹{{ number_format($report['total_debit'], 2) }}</td>
                <td class="text-right">₹{{ number_format($report['total_credit'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
