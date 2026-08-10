<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
            color: #1f2937;
        }
        h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 8px;
        }
        .meta {
            text-align: center;
            margin-bottom: 18px;
            color: #6b7280;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #0f766e;
            color: #ffffff;
            border: 1px solid #d1d5db;
            padding: 8px 6px;
            text-align: left;
            font-size: 10px;
        }
        td {
            border: 1px solid #d1d5db;
            padding: 7px 6px;
            vertical-align: top;
            word-break: break-word;
        }
        tr:nth-child(even) td {
            background: #f8fafc;
        }
        .empty {
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    @include('exports._meta')

    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $column)
                        <td>{{ $row[$column] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="empty">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
