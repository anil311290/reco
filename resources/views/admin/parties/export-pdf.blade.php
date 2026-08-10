<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Party Ledger - {{ $party->name }}</title>
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
    <h1>Party Transaction History</h1>
    <div class="meta">
        {{ $party->name }} ({{ $party->party_code }})
        &middot; {{ ucfirst($party->type) }}
    </div>

    @include('exports._meta')

    <table class="summary">
        <tr>
            <td><strong>Total Debit:</strong> &#8377; {{ number_format($ledger['total_debit'], 2) }}</td>
            <td><strong>Total Credit:</strong> &#8377; {{ number_format($ledger['total_credit'], 2) }}</td>
            <td>
                <strong>Closing:</strong>
                &#8377; {{ number_format($ledger['closing_balance'], 2) }}
                @drCr($ledger['closing_type'])
            </td>
        </tr>
    </table>

    <table class="history">
        <thead>
            <tr>
                <th>Date</th>
                <th>Voucher #</th>
                <th>Particulars</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledger['rows'] as $row)
                @php($entry = $row['entry'])
                <tr>
                    <td>{{ \App\Helpers\DateHelper::formatDate($entry->transaction_date) }}</td>
                    <td>{{ $entry->voucher?->voucher_number ?: '—' }}</td>
                    <td>{{ $entry->description ?: ($entry->voucher->narration ?? '—') }}</td>
                    <td class="num">{{ $entry->debit > 0 ? number_format((float) $entry->debit, 2) : '—' }}</td>
                    <td class="num">{{ $entry->credit > 0 ? number_format((float) $entry->credit, 2) : '—' }}</td>
                    <td class="num">
                        {{ number_format($row['running_balance'], 2) }}
                        @drCr($row['running_type'])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">No transactions found</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($ledger['rows']) > 0)
        <tfoot>
            <tr>
                <td colspan="3" class="num">Total</td>
                <td class="num">{{ number_format($ledger['total_debit'], 2) }}</td>
                <td class="num">{{ number_format($ledger['total_credit'], 2) }}</td>
                <td class="num">
                    {{ number_format($ledger['closing_balance'], 2) }}
                    @drCr($ledger['closing_type'])
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        <p>This is an automatically generated report. Please verify all information before use.</p>
    </div>
</body>
</html>
