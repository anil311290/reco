<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
        }
        .summary,
        .summary th,
        .summary td {
            border: 1px solid #ddd;
            padding: 12px 10px;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary th {
            background: #f7f7f7;
            text-align: left;
        }
        .section-title {
            margin: 20px 0 10px;
            font-size: 18px;
            color: #222;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background: #f3f3f3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Balance Sheet</h1>
            <p>Assets, liabilities, and equity summary for the selected financial year.</p>
        </div>

        <div class="section-title">Assets</div>
        <table class="summary">
            <tbody>
                @forelse($report['assets']['accounts'] as $item)
                    <tr>
                        <td>{{ $item['account']->account_name }}</td>
                        <td class="text-right">₹{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No asset accounts found.</td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    <td>Total Assets</td>
                    <td class="text-right">₹{{ number_format($report['assets']['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Liabilities</div>
        <table class="summary">
            <tbody>
                @forelse($report['liabilities']['accounts'] as $item)
                    <tr>
                        <td>{{ $item['account']->account_name }}</td>
                        <td class="text-right">₹{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No liability accounts found.</td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    <td>Total Liabilities</td>
                    <td class="text-right">₹{{ number_format($report['liabilities']['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Equity</div>
        <table class="summary">
            <tbody>
                @forelse($report['equity']['accounts'] as $item)
                    <tr>
                        <td>{{ $item['account']->account_name }}</td>
                        <td class="text-right">₹{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No equity accounts found.</td>
                    </tr>
                @endforelse
                <tr class="total-row">
                    <td>Total Equity</td>
                    <td class="text-right">₹{{ number_format($report['equity']['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="summary">
            <tbody>
                <tr class="total-row">
                    <td>Total Liabilities + Equity</td>
                    <td class="text-right">₹{{ number_format($report['total_liabilities_equity'], 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Balance Status</td>
                    <td class="text-right">{{ $report['is_balanced'] ? 'Balanced' : 'Review Needed' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
