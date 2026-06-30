<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creditors Outstanding Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .container { padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 22px; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .table th {
            background: #f7f7f7;
            text-align: left;
        }
        .text-right { text-align: right; }
        tfoot td { font-weight: bold; background: #f3f3f3; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Creditors Outstanding</h1>
        <p>Total Payable: ₹{{ number_format($report['total'], 2) }}</p>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>#</th>
            <th>Party</th>
            <th>Mobile</th>
            <th>Email</th>
            <th class="text-right">Balance (₹)</th>
        </tr>
        </thead>
        <tbody>
        @forelse($report['creditors'] as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['party']->name }}</td>
                <td>{{ $item['party']->mobile ?? '-' }}</td>
                <td>{{ $item['party']->email ?? '-' }}</td>
                <td class="text-right">{{ number_format($item['balance'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No outstanding creditors found</td></tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr>
            <td colspan="4">Total Payable</td>
            <td class="text-right">₹{{ number_format($report['total'], 2) }}</td>
        </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
