<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Balance Report</title>
    <style>
        @page { margin: 10px 10px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 10px;
        }
        .container { padding: 0; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 4px 0 0; color: #6b7280; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .table th, .table td {
            border: 1px solid #d6dbe6;
            padding: 5px 4px;
        }
        .table th {
            background: #f3f6fc;
            text-align: left;
            font-weight: 700;
        }
        .text-right { text-align: right; }
        tfoot td {
            font-weight: bold;
            background: #eef2f8;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Trial Balance</h1>
        <p>Opening · Transactions · Closing — Generated on {{ now()->format('d-M-Y H:i:s') }}</p>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>Code</th>
            <th>Account</th>
            <th>Type</th>
            <th>Dest</th>
            <th class="text-right">Op Dr</th>
            <th class="text-right">Op Cr</th>
            <th class="text-right">Tr Dr</th>
            <th class="text-right">Tr Cr</th>
            <th class="text-right">Cl Dr</th>
            <th class="text-right">Cl Cr</th>
        </tr>
        </thead>
        <tbody>
        @forelse($report['accounts'] as $item)
            <tr>
                <td>{{ $item['account']->account_code }}</td>
                <td>{{ $item['account']->account_name }}</td>
                <td>{{ ucfirst($item['account']->account_type) }}</td>
                <td>{{ $item['destination'] ?? '-' }}</td>
                <td class="text-right">{{ ($item['opening_debit'] ?? 0) > 0 ? number_format($item['opening_debit'], 2) : '-' }}</td>
                <td class="text-right">{{ ($item['opening_credit'] ?? 0) > 0 ? number_format($item['opening_credit'], 2) : '-' }}</td>
                <td class="text-right">{{ ($item['transaction_debit'] ?? 0) > 0 ? number_format($item['transaction_debit'], 2) : '-' }}</td>
                <td class="text-right">{{ ($item['transaction_credit'] ?? 0) > 0 ? number_format($item['transaction_credit'], 2) : '-' }}</td>
                <td class="text-right">{{ ($item['debit'] ?? 0) > 0 ? number_format($item['debit'], 2) : '-' }}</td>
                <td class="text-right">{{ ($item['credit'] ?? 0) > 0 ? number_format($item['credit'], 2) : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="10">No records found</td></tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr>
            <td colspan="4">Total</td>
            <td class="text-right">{{ number_format($report['total_opening_debit'] ?? 0, 2) }}</td>
            <td class="text-right">{{ number_format($report['total_opening_credit'] ?? 0, 2) }}</td>
            <td class="text-right">{{ number_format($report['total_transaction_debit'] ?? 0, 2) }}</td>
            <td class="text-right">{{ number_format($report['total_transaction_credit'] ?? 0, 2) }}</td>
            <td class="text-right">{{ number_format($report['total_debit'], 2) }}</td>
            <td class="text-right">{{ number_format($report['total_credit'], 2) }}</td>
        </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
