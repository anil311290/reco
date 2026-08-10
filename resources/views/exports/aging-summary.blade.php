<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aging Summary</title>
    <style>
        @page { margin: 10px 12px; }
        body { font-family: DejaVu Sans, sans-serif; color: #333; font-size: 11px; margin: 0; }
        .container { padding: 0; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; }
        .meta { margin-top: 4px; font-size: 10px; color: #666; }
        .summary { margin-bottom: 8px; }
        .summary td { padding: 4px 6px; border: 1px solid #d6dbe6; }
        .summary .label { background: #f3f6fc; font-weight: 700; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #d6dbe6; padding: 6px; }
        .table th { background: #f3f6fc; text-align: left; font-weight: 700; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Aging Summary</h1>
        <p class="meta">Period: {{ !empty($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '-' }} to {{ !empty($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '-' }}</p>
    </div>
    @include('exports._meta')

    <table class="summary" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="label">Receivables Outstanding</td>
            <td class="text-right">₹{{ number_format((float) ($summary['receivables_total'] ?? 0), 2) }}</td>
            <td class="label">Payables Outstanding</td>
            <td class="text-right">₹{{ number_format((float) ($summary['payables_total'] ?? 0), 2) }}</td>
        </tr>
    </table>

    <table class="table">
        <thead>
        <tr>
            <th>#</th>
            <th>Type</th>
            <th>Party</th>
            <th>Mobile</th>
            <th>Oldest Due Date</th>
            <th>Overdue By</th>
            <th class="text-right">Overdue (₹)</th>
            <th class="text-right">Balance (₹)</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['type'] ?? '-' }}</td>
                <td>{{ $item['party'] ?? '-' }}</td>
                <td>{{ $item['mobile'] ?? '-' }}</td>
                <td>{{ !empty($item['oldest_due_date']) && $item['oldest_due_date'] !== '-' ? \Carbon\Carbon::parse($item['oldest_due_date'])->format('d/m/Y') : '-' }}</td>
                <td>{{ ((int) ($item['overdue_days'] ?? 0)) > 0 ? ((int) ($item['overdue_days'] ?? 0) . ' days late') : 'Current' }}</td>
                <td class="text-right">{{ number_format((float) ($item['overdue_amount'] ?? 0), 2) }}</td>
                <td class="text-right">{{ number_format((float) ($item['balance'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No aging records found</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
