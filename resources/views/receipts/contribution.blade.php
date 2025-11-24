<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $contribution->receipt_number }}</title>
    <style>
        @page {
            margin: 12mm;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        .receipt-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 5px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 3px;
            max-height: 80px;
        }

        .logo-container img {
            max-width: 78px;
            max-height: 78px;
            width: auto;
            height: auto;
            display: block;
        }

        .organization-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .organization-details {
            font-size: 9pt;
            color: #333;
            line-height: 1.6;
        }

        .receipt-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin: 8px 0 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 8px;
            background: #f5f5f5;
            border: 1px solid #ddd;
        }

        .receipt-info-item {
            flex: 1;
        }

        .receipt-info-label {
            font-weight: bold;
            font-size: 9pt;
            color: #666;
            margin-bottom: 3px;
        }

        .receipt-info-value {
            font-size: 10pt;
            font-weight: bold;
        }

        .section {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 6px;
            padding-bottom: 2px;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 12px;
        }

        .info-item {
            margin-bottom: 6px;
        }

        .info-label {
            font-size: 9pt;
            color: #666;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 10pt;
            font-weight: bold;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }

        .details-table th,
        .details-table td {
            padding: 6px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 10pt;
        }

        .details-table th {
            background: #f5f5f5;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9pt;
        }

        .details-table td {
            font-size: 10pt;
        }

        .details-table small {
            font-size: 8pt;
        }

        .text-right {
            text-align: right;
        }

        .totals {
            margin-top: 6px;
            margin-left: auto;
            width: 300px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #ddd;
            font-size: 10pt;
        }

        .total-row.final {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            margin-top: 6px;
            padding: 8px 0;
            font-size: 12pt;
            font-weight: bold;
        }

        .total-label {
            font-weight: bold;
        }

        .total-value {
            font-weight: bold;
        }

        .footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }

        .signature-section {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 250px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 20px;
            padding-top: 3px;
            font-size: 9pt;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72pt;
            color: rgba(0, 0, 0, 0.05);
            font-weight: bold;
            z-index: -1;
            pointer-events: none;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="watermark">OFFICIAL RECEIPT</div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <x-app-logo-icon />
            </div>
            <div class="organization-name">
                {{ app(\App\Services\SettingService::class)->get('organization_info.name', 'Maina Community Development Foundation') }}
            </div>
            <div class="organization-details">
                @php
                    $orgInfo = app(\App\Services\SettingService::class)->get('organization_info', []);
                @endphp
                @if(!empty($orgInfo['address']))
                    {{ $orgInfo['address'] }}<br>
                @endif
                @if(!empty($orgInfo['phone']))
                    Phone: {{ $orgInfo['phone'] }}
                @endif
                @if(!empty($orgInfo['email']))
                    | Email: {{ $orgInfo['email'] }}
                @endif
                @if(!empty($orgInfo['website']))
                    | Website: {{ $orgInfo['website'] }}
                @endif
            </div>
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">Payment Receipt</div>

        <!-- Receipt Information -->
        <div class="receipt-info">
            <div class="receipt-info-item">
                <div class="receipt-info-label">Receipt Number</div>
                <div class="receipt-info-value">{{ $contribution->receipt_number }}</div>
            </div>
            <div class="receipt-info-item">
                <div class="receipt-info-label">Date</div>
                <div class="receipt-info-value">{{ $contribution->payment_date->format('F d, Y') }}</div>
            </div>
            @if($contribution->payment_reference)
            <div class="receipt-info-item">
                <div class="receipt-info-label">Reference</div>
                <div class="receipt-info-value">{{ $contribution->payment_reference }}</div>
            </div>
            @endif
        </div>

        <!-- Member Information -->
        <div class="section">
            <div class="section-title">Member Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value">{{ $contribution->member->full_name }} {{ $contribution->member->family_name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Registration Number</div>
                    <div class="info-value">{{ $contribution->member->registration_no }}</div>
                </div>
                @if($contribution->member->phone)
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value">{{ $contribution->member->phone }}</div>
                </div>
                @endif
                @if($contribution->member->address)
                <div class="info-item">
                    <div class="info-label">Address</div>
                    <div class="info-value">{{ $contribution->member->address }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Contribution Details -->
        <div class="section">
            <div class="section-title">Contribution Details</div>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Amount (NGN)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>{{ $contribution->contributionPlan?->label ?? 'Contribution' }}</strong><br>
                            <small>Period: {{ $contribution->period_start->format('M d, Y') }} - {{ $contribution->period_end->format('M d, Y') }}</small>
                        </td>
                        <td class="text-right">{{ number_format($contribution->amount, 2) }}</td>
                    </tr>
                    @if($contribution->fine_amount > 0)
                    <tr>
                        <td>
                            <strong>Late Payment Fine</strong><br>
                            <small>Payment received {{ $contribution->payment_date->diffInDays($contribution->period_end) }} days after due date</small>
                        </td>
                        <td class="text-right">{{ number_format($contribution->fine_amount, 2) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <div class="totals">
                @if($contribution->fine_amount > 0)
                <div class="total-row">
                    <span class="total-label">Subtotal:</span>
                    <span class="total-value">NGN {{ number_format($contribution->amount, 2) }}</span>
                </div>
                <div class="total-row">
                    <span class="total-label">Late Fine:</span>
                    <span class="total-value">NGN {{ number_format($contribution->fine_amount, 2) }}</span>
                </div>
                @endif
                <div class="total-row final">
                    <span class="total-label">Total Amount Paid:</span>
                    <span class="total-value">NGN {{ number_format($contribution->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="section">
            <div class="section-title">Payment Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">{{ $contribution->payment_method_label }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Date</div>
                    <div class="info-value">{{ $contribution->payment_date->format('F d, Y') }}</div>
                </div>
                @if($contribution->payment_reference)
                <div class="info-item">
                    <div class="info-label">Payment Reference</div>
                    <div class="info-value">{{ $contribution->payment_reference }}</div>
                </div>
                @endif
                @if($contribution->collector)
                <div class="info-item">
                    <div class="info-label">Collected By</div>
                    <div class="info-value">{{ $contribution->collector->name }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes -->
        @if($contribution->notes)
        <div class="section">
            <div class="section-title">Notes</div>
            <div style="padding: 10px; background: #f9f9f9; border-left: 3px solid #000;">
                {{ $contribution->notes }}
            </div>
        </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Member Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>This is an official receipt. Please keep it for your records.</strong></p>
            <p style="margin-top: 10px;">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
        </div>
    </div>
</body>
</html>

