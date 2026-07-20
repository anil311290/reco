<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Item Stock History - {{ $item->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
            color: #212529;
        }
        h1 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 4px;
        }
        .meta {
            text-align: center;
            margin-bottom: 16px;
            color: #555;
            font-size: 10px;
        }
        .summary {
            margin-bottom: 14px;
            width: 100%;
        }
        .summary td {
            padding: 3px 8px 3px 0;
        }
        table.history {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.history th {
            background-color: #343a40;
            color: #fff;
            padding: 7px 6px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        table.history th.num,
        table.history td.num {
            text-align: right;
        }
        table.history td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        table.history tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        table.history tfoot td {
            font-weight: bold;
            background-color: #e9ecef;
        }
        .footer {
            margin-top: 24px;
            text-align: right;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Stock &amp; Transaction History</h1>
    <div class="meta">
        {{ $item->name }} ({{ $item->item_code }})
        &middot; Generated on {{ now()->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
    </div>

    <table class="summary">
        <tr>
            <td><strong>Type:</strong> {{ ucfirst($item->type) }}</td>
            <td><strong>Unit:</strong> {{ $item->unit ?: '-' }}</td>
            <td><strong>Current Stock:</strong> {{ number_format((float) $item->current_stock, 3) }}</td>
        </tr>
        <tr>
            <td><strong>Qty In:</strong> {{ number_format($history['total_in'], 3) }}</td>
            <td><strong>Qty Out:</strong> {{ number_format($history['total_out'], 3) }}</td>
            <td><strong>Closing Qty:</strong> {{ number_format($history['closing_qty'], 3) }}</td>
        </tr>
    </table>

    <table class="history">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Invoice #</th>
                <th>Party</th>
                <th class="num">Qty In</th>
                <th class="num">Qty Out</th>
                <th class="num">Rate</th>
                <th class="num">Amount</th>
                <th class="num">Balance Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse($history['rows'] as $row)
            <tr>
                <td>{{ $row['date'] ? \App\Helpers\DateHelper::formatDate($row['date']) : '—' }}</td>
                <td>{{ $row['type_label'] }}</td>
                <td>{{ $row['invoice_number'] ?: '—' }}</td>
                <td>{{ $row['party_name'] ?: '—' }}</td>
                <td class="num">{{ $row['qty_in'] > 0 ? number_format($row['qty_in'], 3) : '—' }}</td>
                <td class="num">{{ $row['qty_out'] > 0 ? number_format($row['qty_out'], 3) : '—' }}</td>
                <td class="num">{{ $row['rate'] > 0 ? number_format($row['rate'], 2) : '—' }}</td>
                <td class="num">{{ $row['amount'] > 0 ? number_format($row['amount'], 2) : '—' }}</td>
                <td class="num">{{ number_format($row['running_qty'], 3) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center;">No transactions found</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($history['rows']) > 0)
        <tfoot>
            <tr>
                <td colspan="4" class="num">Total</td>
                <td class="num">{{ number_format($history['total_in'], 3) }}</td>
                <td class="num">{{ number_format($history['total_out'], 3) }}</td>
                <td></td>
                <td></td>
                <td class="num">{{ number_format($history['closing_qty'], 3) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        <p>This is an automatically generated report. Please verify all information before use.</p>
    </div>
</body>
</html>
