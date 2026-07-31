<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt &amp; Payment</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
        }
        .header {
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0 0 6px;
            font-size: 20px;
        }
        .meta {
            margin: 2px 0;
            color: #4b5563;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f3f4f6;
        }
        .text-right {
            text-align: right;
        }
        .total-row td {
            background: #e5e7eb;
            font-weight: 700;
        }
        .muted {
            color: #6b7280;
        }
        .side {
            width: 49%;
            vertical-align: top;
        }
        .layout, .layout td {
            border: 0;
            padding: 0;
        }
        .spacer {
            width: 2%;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Receipt &amp; Payment</h1>
        <div class="meta">
            Period: {{ \App\Helpers\DateHelper::formatDate($report['date_from']) }} to {{ \App\Helpers\DateHelper::formatDate($report['date_to']) }}
        </div>
        @if($report['message'])
            <div class="meta">{{ $report['message'] }}</div>
        @endif
    </div>

    @if(!$report['message'])
        <table class="layout">
            <tr>
                <td class="side">
                    <table>
                        <thead>
                            <tr>
                                <th>Receipts</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Opening Balance b/f</td>
                                <td class="text-right">{{ number_format((float) $report['opening_total'], 2) }}</td>
                            </tr>
                            @forelse($report['receipts']['rows'] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-right">{{ number_format((float) $row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="muted">No receipts in this period.</td>
                                </tr>
                            @endforelse
                            <tr class="total-row">
                                <td>Total</td>
                                <td class="text-right">{{ number_format((float) $report['receipts_side_total'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="spacer"></td>
                <td class="side">
                    <table>
                        <thead>
                            <tr>
                                <th>Payments</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['payments']['rows'] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-right">{{ number_format((float) $row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="muted">No payments in this period.</td>
                                </tr>
                            @endforelse
                            <tr>
                                <td>Closing Balance c/f</td>
                                <td class="text-right">{{ number_format((float) $report['closing_total'], 2) }}</td>
                            </tr>
                            <tr class="total-row">
                                <td>Total</td>
                                <td class="text-right">{{ number_format((float) $report['payments_side_total'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <h3>Cash / Bank Ledgers</h3>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Ledger</th>
                    <th class="text-right">Opening</th>
                    <th class="text-right">Received</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Closing</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['accounts'] as $row)
                    <tr>
                        <td>{{ $row['account']->account_code }}</td>
                        <td>{{ $row['account']->account_name }}</td>
                        <td class="text-right">{{ number_format((float) $row['opening'], 2) }}</td>
                        <td class="text-right">{{ number_format((float) $row['received'], 2) }}</td>
                        <td class="text-right">{{ number_format((float) $row['paid'], 2) }}</td>
                        <td class="text-right">{{ number_format((float) $row['closing'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">Total</td>
                    <td class="text-right">{{ number_format((float) $report['opening_total'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) collect($report['accounts'])->sum('received'), 2) }}</td>
                    <td class="text-right">{{ number_format((float) collect($report['accounts'])->sum('paid'), 2) }}</td>
                    <td class="text-right">{{ number_format((float) $report['closing_total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
