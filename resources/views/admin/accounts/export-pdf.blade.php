<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Accounts Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .currency {
            text-align: right;
        }
        .center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Account Master Report</h1>
    <p>Generated on: {{ now()->format('d-m-Y H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>Opening Balance</th>
                <th>Balance Type</th>
                <th>Opening Date</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accounts as $account)
            <tr>
                <td>{{ $account->account_code }}</td>
                <td>{{ $account->account_name }}</td>
                <td class="center">{{ ucfirst($account->account_type) }}</td>
                <td class="currency">₹ {{ number_format($account->opening_balance, 2) }}</td>
                <td class="center">{{ ucfirst($account->balance_type ?? 'Debit') }}</td>
                <td class="center">{{ $account->opening_date?->format('d-m-Y') ?? '-' }}</td>
                <td class="center">{{ $account->is_active ? 'Active' : 'Inactive' }}</td>
                <td>{{ $account->remarks ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="center">No accounts found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is an automatically generated report. Please verify all information before use.</p>
    </div>
</body>
</html>
