<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payables Outstanding</title>
    <style>
        @page { margin: 10px 12px; }
        body { font-family: DejaVu Sans, sans-serif; color: #333; font-size: 11px; margin: 0; }
        .container { padding: 0; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #d6dbe6; padding: 6px; }
        .table th { background: #f3f6fc; text-align: left; font-weight: 700; }
        .text-right { text-align: right; }
        tfoot td { font-weight: bold; background: #eef2f8; }
    </style>
</head>
<body>
<div class="container">
    <div class="header"><h1>Payables Outstanding</h1></div>
    @include('exports._meta')
    <table class="table">
        <thead>
        <tr>
            <th>#</th>
            <th>Invoice No</th>
            <th>Party</th>
            <th>Invoice Date</th>
            <th>Due Date</th>
            <th class="text-right">Billed Days</th>
            <th class="text-right">Due Days</th>
            <th class="text-right">Amount (₹)</th>
            <th class="text-right">Paid (₹)</th>
            <th class="text-right">Balance (₹)</th>
        </tr>
        </thead>
        <tbody>
        @forelse($report['creditors'] as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ ($item['invoice_number'] ?? '-') . ' / ' . ($item['party']->party_code ?? '-') }}</td>
                <td>{{ $item['party']->name }}</td>
                <td>{{ !empty($item['invoice_date']) ? \Carbon\Carbon::parse($item['invoice_date'])->format('d/m/Y') : '-' }}</td>
                <td>{{ !empty($item['due_date']) ? \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') : '-' }}</td>
                <td class="text-right">{{ $item['billed_days'] ?? 0 }}</td>
                <td class="text-right">{{ $item['due_days'] ?? 0 }}</td>
                <td class="text-right">{{ number_format((float) ($item['invoice_total'] ?? 0), 2) }}</td>
                <td class="text-right">{{ number_format((float) ($item['amount_paid'] ?? 0), 2) }}</td>
                <td class="text-right">{{ number_format($item['balance'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="10">No outstanding payables</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="9">Total Outstanding</td>
                <td class="text-right">₹{{ number_format($report['total'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
