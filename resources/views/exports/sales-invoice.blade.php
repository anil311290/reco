<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #222;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.4;
        }
        .container {
            padding: 24px;
        }
        .header-table,
        .info-table,
        .items-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            text-align: right;
            color: #1a365d;
        }
        .invoice-number {
            text-align: right;
            font-size: 13px;
            color: #555;
            margin-top: 4px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .info-box {
            border: 1px solid #d9d9d9;
            padding: 10px;
            margin-top: 16px;
            min-height: 90px;
        }
        .info-table {
            margin-top: 16px;
        }
        .info-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
        }
        .meta-row {
            margin-bottom: 3px;
        }
        .meta-label {
            color: #666;
            display: inline-block;
            width: 90px;
        }
        .items-table {
            margin-top: 18px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ccc;
            padding: 7px 6px;
        }
        .items-table th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-wrap {
            margin-top: 12px;
        }
        .totals-table {
            width: 280px;
            margin-left: auto;
        }
        .totals-table td {
            padding: 5px 0;
        }
        .totals-table .grand-total td {
            border-top: 2px solid #222;
            font-size: 14px;
            font-weight: bold;
            padding-top: 8px;
        }
        .notes {
            margin-top: 18px;
            padding: 10px;
            border: 1px solid #e5e5e5;
            background: #fafafa;
        }
        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #777;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 10px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <table class="header-table">
            <tr>
                <td>
                    <div class="company-name">{{ $invoice->company->name ?? 'Company' }}</div>
                    @if($invoice->company?->address)
                        <div>{{ $invoice->company->address }}</div>
                    @endif
                    @if($invoice->company?->city || $invoice->company?->state)
                        <div>{{ trim(($invoice->company->city ?? '') . ', ' . ($invoice->company->state ?? ''), ', ') }}</div>
                    @endif
                    @if($invoice->company?->phone)
                        <div>Phone: {{ $invoice->company->phone }}</div>
                    @endif
                    @if($invoice->company?->email)
                        <div>Email: {{ $invoice->company->email }}</div>
                    @endif
                    @if($invoice->company?->gst_number)
                        <div><strong>GSTIN:</strong> {{ $invoice->company->gst_number }}</div>
                    @endif
                    @if($invoice->company?->pan_number)
                        <div><strong>PAN:</strong> {{ $invoice->company->pan_number }}</div>
                    @endif
                </td>
                <td>
                    <div class="invoice-title">TAX INVOICE</div>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td>
                    <div class="info-box">
                        <div class="section-title">Bill To</div>
                        <strong>{{ $invoice->party->name ?? 'N/A' }}</strong><br>
                        @if($invoice->party?->address)
                            {{ $invoice->party->address }}<br>
                        @endif
                        @if($invoice->party?->city || $invoice->party?->state)
                            {{ trim(($invoice->party->city ?? '') . ', ' . ($invoice->party->state ?? ''), ', ') }}<br>
                        @endif
                        @if($invoice->party?->mobile)
                            Phone: {{ $invoice->party->mobile }}<br>
                        @endif
                        @if($invoice->party?->gstin)
                            <strong>GSTIN:</strong> {{ $invoice->party->gstin }}
                        @endif
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="section-title">Invoice Details</div>
                        <div class="meta-row">
                            <span class="meta-label">Invoice #</span>
                            <strong>{{ $invoice->invoice_number }}</strong>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Date</span>
                            {{ $invoice->invoice_date?->format('d M Y') ?? '-' }}
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Due Date</span>
                            {{ $invoice->due_date?->format('d M Y') ?? '-' }}
                        </div>
                        @if($invoice->reference_number)
                        <div class="meta-row">
                            <span class="meta-label">Reference</span>
                            {{ $invoice->reference_number }}
                        </div>
                        @endif
                        <div class="meta-row">
                            <span class="meta-label">Status</span>
                            <span class="status-badge">{{ ucfirst($invoice->status) }}</span>
                        </div>
                        @if($invoice->financialYear)
                        <div class="meta-row">
                            <span class="meta-label">FY</span>
                            {{ $invoice->financialYear->name }}
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 34%;">Description</th>
                    <th class="text-right" style="width: 10%;">Qty</th>
                    <th class="text-right" style="width: 14%;">Unit Price</th>
                    <th class="text-right" style="width: 10%;">Disc %</th>
                    <th class="text-right" style="width: 14%;">Tax</th>
                    <th class="text-right" style="width: 14%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->lines as $index => $line)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $line->item->name ?? $line->account->account_name ?? $line->description ?? '-' }}
                        @if($line->description && ($line->item || $line->account))
                            <br><span style="color:#666;">{{ $line->description }}</span>
                        @endif
                        @if($line->taxRate)
                            <br><span style="color:#666; font-size:9px;">{{ $line->taxRate->tax_name }} ({{ number_format($line->taxRate->tax_rate, 2) }}%)</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($line->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($line->discount_percentage, 2) }}</td>
                    <td class="text-right">{{ number_format($line->tax_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($line->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-wrap">
            <table class="totals-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td>Discount</td>
                    <td class="text-right">-{{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>Tax</td>
                    <td class="text-right">{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Total ({{ $invoice->currency ?? 'INR' }})</td>
                    <td class="text-right">{{ number_format($invoice->total, 2) }}</td>
                </tr>
                @if($invoice->amount_paid > 0)
                <tr>
                    <td>Paid</td>
                    <td class="text-right">{{ number_format($invoice->amount_paid, 2) }}</td>
                </tr>
                @endif
                @if($invoice->balance_due > 0)
                <tr>
                    <td><strong>Balance Due</strong></td>
                    <td class="text-right"><strong>{{ number_format($invoice->balance_due, 2) }}</strong></td>
                </tr>
                @endif
            </table>
        </div>

        @if($invoice->notes)
        <div class="notes">
            <strong>Notes:</strong> {{ $invoice->notes }}
        </div>
        @endif

        <div class="footer">
            Generated on {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>
</body>
</html>
