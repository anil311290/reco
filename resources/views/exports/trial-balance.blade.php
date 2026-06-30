<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Balance Report</title>
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
        tfoot td {
            font-weight: bold;
            background: #f3f3f3;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Trial Balance</h1>
        <p>Generated on {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>Account Code</th>
            <th>Account Name</th>
            <th class="text-right">Debit (₹)</th>
            <th class="text-right">Credit (₹)</th>
        </tr>
        </thead>
        <tbody>
        @forelse($report['accounts'] as $item)
            <tr>
                <td>{{ $item['account']->account_code }}</td>
                <td>{{ $item['account']->account_name }}</td>
                <td class="text-right">{{ $item['debit'] > 0 ? number_format($item['debit'], 2) : '-' }}</td>
                <td class="text-right">{{ $item['credit'] > 0 ? number_format($item['credit'], 2) : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No records found</td></tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr>
            <td colspan="2">Total</td>
            <td class="text-right">₹{{ number_format($report['total_debit'], 2) }}</td>
            <td class="text-right">₹{{ number_format($report['total_credit'], 2) }}</td>
        </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
