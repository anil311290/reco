<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Flow Report</title>
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
        .summary {
            margin-top: 20px;
            border-collapse: collapse;
            width: 100%;
        }
        .summary th,
        .summary td {
            padding: 12px 10px;
            border: 1px solid #ddd;
        }
        .summary th {
            background: #f7f7f7;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .positive {
            color: #155724;
        }
        .negative {
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cash Flow Statement</h1>
        </div>

        <table class="summary">
            <tbody>
                <tr>
                    <th>Cash Inflows</th>
                    <td class="text-right">₹{{ number_format($report['inflows'], 2) }}</td>
                </tr>
                <tr>
                    <th>Cash Outflows</th>
                    <td class="text-right">₹{{ number_format($report['outflows'], 2) }}</td>
                </tr>
                <tr>
                    <th>Net Cash Flow</th>
                    <td class="text-right {{ $report['net_cash_flow'] >= 0 ? 'positive' : 'negative' }}">
                        ₹{{ number_format($report['net_cash_flow'], 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
