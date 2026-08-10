<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .container {
            padding: 20px;
        }
        .header {
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .muted {
            color: #666;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .meta td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .table th {
            background: #f7f7f7;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .footer-note {
            margin-top: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Voucher</h1>
            <div class="muted">{{ optional($voucher)->voucher_number ?? 'N/A' }}</div>
        </div>

        <table class="meta">
            <tr>
                <td><strong>Company</strong><br>{{ optional($voucher->company)->name ?? '-' }}</td>
                <td><strong>Date</strong><br>@istDate(optional($voucher)->voucher_date)</td>
                <td><strong>Type</strong><br>{{ ucfirst(optional($voucher)->voucher_type ?? '-') }}</td>
            </tr>
            <tr>
                <td><strong>Party</strong><br>{{ optional($voucher->party)->name ?? '-' }}</td>
                <td><strong>Status</strong><br>{{ ucfirst(optional($voucher)->status ?? '-') }}</td>
                <td><strong>Narration</strong><br>{{ optional($voucher)->narration ?: '-' }}</td>
            </tr>
        </table>

        @include('exports._meta')

        <table class="table">
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse(optional($voucher)->lines ?? [] as $line)
                    <tr>
                        <td>{{ $line->account->account_name ?? '-' }}</td>
                        <td class="text-right">{{ number_format((float) $line->debit, 2) }}</td>
                        <td class="text-right">{{ number_format((float) $line->credit, 2) }}</td>
                        <td>{{ $line->description ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-right">No voucher lines found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th class="text-right">Totals</th>
                    <th class="text-right">{{ number_format((float) (optional($voucher)->total_debit ?? 0), 2) }}</th>
                    <th class="text-right">{{ number_format((float) (optional($voucher)->total_credit ?? 0), 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>

        <div class="footer-note">
            <strong>Remarks:</strong> {{ optional($voucher)->remarks ?: '-' }}
        </div>
    </div>
</body>
</html>
