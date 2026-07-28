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
            font-size: 11px;
        }
        .container { padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 6px;
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
        <p>Opening · Transactions · Closing — Generated on {{ now()->format('d-m-Y H:i:s') }}</p>
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
