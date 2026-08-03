<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet Report</title>
    <style>
        @page { margin: 10px 12px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 11px;
        }
        .container {
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 4px 0 0;
            color: #6b7280;
        }
        .summary,
        .summary th,
        .summary td {
            border: 1px solid #d6dbe6;
            padding: 7px 8px;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .summary th {
            background: #f3f6fc;
            text-align: left;
        }
        .section-title {
            margin: 14px 0 6px;
            font-size: 14px;
            color: #222;
            font-weight: 700;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background: #eef2f8;
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
