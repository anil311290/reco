<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receivables Outstanding</title>
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
    <div class="header"><h1>Receivables Outstanding</h1></div>
    @include('exports._meta')
    <table class="table">
        <thead>
        <tr>
            <th>#</th>
            <th>Party</th>
            <th>Mobile</th>
            <th>Email</th>
            <th>Oldest Due Date</th>
            <th>Overdue By</th>
            <th class="text-right">Overdue (₹)</th>
            <th class="text-right">Balance (₹) Dr</th>
        </tr>
        </thead>
        <tbody>
        @forelse($report['debtors'] as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['party']->name }}</td>
                <td>{{ $item['party']->mobile ?? '-' }}</td>
                <td>{{ $item['party']->email ?? '-' }}</td>
                <td>{{ !empty($item['oldest_due_date']) ? \Carbon\Carbon::parse($item['oldest_due_date'])->format('d/m/Y') : '-' }}</td>
                <td>{{ $item['overdue_label'] ?? 'Current' }}</td>
                <td class="text-right">{{ number_format((float) ($item['overdue_amount'] ?? 0), 2) }}</td>
                <td class="text-right">{{ number_format($item['balance'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No outstanding receivables</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">Total Outstanding</td>
                <td class="text-right">₹{{ number_format($report['total'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
