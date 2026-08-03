<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit and Loss Report</title>
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
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 4px 0 0; color: #6b7280; }
        .summary, .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .summary td, .summary th, .table td, .table th {
            border: 1px solid #d6dbe6;
            padding: 6px 7px;
        }
        .summary th, .table th {
            background: #f3f6fc;
            text-align: left;
            font-weight: 700;
        }
        .text-right { text-align: right; }
        .positive { color: #155724; }
        .negative { color: #721c24; }
        .section { margin-top: 12px; }
        .section h3 { margin: 0 0 4px; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Profit and Loss Statement</h1>
        <p>Generated on {{ now()->format('d-M-Y H:i:s') }}</p>
    </div>

    <table class="summary">
        <tr>
            <th>Total Income</th>
            <td class="text-right">₹{{ number_format($report['income']['total'], 2) }}</td>
        </tr>
        <tr>
            <th>Total Expense</th>
            <td class="text-right">₹{{ number_format($report['expense']['total'], 2) }}</td>
        </tr>
        <tr>
            <th>Net {{ $report['is_profit'] ? 'Profit' : 'Loss' }}</th>
            <td class="text-right {{ $report['is_profit'] ? 'positive' : 'negative' }}">{{ $report['is_profit'] ? '+' : '-' }}₹{{ number_format(abs($report['net_profit']), 2) }}</td>
        </tr>
    </table>

    <div class="section">
        <h3>Income Accounts</h3>
        <table class="table">
            <thead>
            <tr><th>Account</th><th class="text-right">Amount</th></tr>
            </thead>
            <tbody>
            @forelse($report['income']['accounts'] as $item)
                <tr>
                    <td>{{ $item['account']->account_name }}</td>
                    <td class="text-right">₹{{ number_format($item['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No income records found</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Expense Accounts</h3>
        <table class="table">
            <thead>
            <tr><th>Account</th><th class="text-right">Amount</th></tr>
            </thead>
            <tbody>
            @forelse($report['expense']['accounts'] as $item)
                <tr>
                    <td>{{ $item['account']->account_name }}</td>
                    <td class="text-right">₹{{ number_format($item['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No expense records found</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
