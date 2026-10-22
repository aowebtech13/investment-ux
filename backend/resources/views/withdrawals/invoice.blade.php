<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Withdrawal Invoice</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 13px;
            color: #1f2937;
            background: #f3f4f6;
            line-height: 1.5;
        }
        .page {
            max-width: 760px;
            margin: 0 auto;
            background: #ffffff;
            padding: 0;
        }
        /* Accent bar */
        .accent-bar {
            height: 6px;
            background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%);
        }
        .inner { padding: 40px 48px; }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 36px;
        }
        .header .brand,
        .header .doc-title {
            display: table-cell;
            vertical-align: middle;
        }
        .header .doc-title { text-align: right; }
        .logo-mark {
            display: inline-block;
            width: 34px; height: 34px;
            border-radius: 8px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            font-weight: 700;
            font-size: 16px;
            text-align: center;
            line-height: 34px;
            margin-right: 10px;
        }
        .brand-name {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            letter-spacing: 0.3px;
        }
        .brand-sub {
            font-size: 11px;
            color: #9ca3af;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .doc-title h1 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .doc-title .ref {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* Info cards */
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 28px;
        }
        .info-card {
            display: table-cell;
            width: 50%;
            padding: 18px 20px;
            background: #f9fafb;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            vertical-align: top;
        }
        .info-card:first-child { padding-right: 24px; }
        .info-card:last-child { padding-left: 24px; text-align: right; }
        .info-card .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        .info-card .value {
            font-size: 14px;
            color: #111827;
            font-weight: 600;
        }
        .info-card .sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* Status badge */
        .badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .badge-pending   { background: #fff7ed; color: #ea580c; }
        .badge-approved  { background: #ecfdf5; color: #059669; }
        .badge-rejected  { background: #fef2f2; color: #dc2626; }
        .badge-default   { background: #f3f4f6; color: #6b7280; }

        /* Section titles */
        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: #6b7280;
            margin: 30px 0 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eef2f7;
        }

        /* Tables */
        table.items {
            width: 100%;
            border-collapse: collapse;
        }
        table.items th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            padding: 10px 12px;
            background: #f9fafb;
            border-bottom: 1px solid #eef2f7;
        }
        table.items td {
            padding: 12px;
            font-size: 13px;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }
        table.items td.right,
        table.items th.right { text-align: right; }
        table.items .desc { font-weight: 600; color: #111827; }
        .cap { text-transform: capitalize; }

        /* Total */
        .total-row {
            display: table;
            width: 100%;
            margin-top: 22px;
        }
        .total-row .spacer { display: table-cell; width: 60%; }
        .total-box {
            display: table-cell;
            width: 40%;
            padding: 16px 20px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 10px;
            color: #fff;
            text-align: right;
        }
        .total-box .t-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            opacity: 0.85;
        }
        .total-box .t-amount {
            font-size: 24px;
            font-weight: 700;
            margin-top: 2px;
        }

        /* Rejection */
        .rejection {
            margin-top: 24px;
            padding: 16px 18px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #b91c1c;
            font-size: 13px;
        }
        .rejection .r-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 18px;
            border-top: 1px solid #eef2f7;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="accent-bar"></div>
        <div class="inner">

            <!-- Header -->
            <div class="header">
                <div class="brand">
                    <span class="logo-mark">N</span>
                    <span class="brand-name">Nexora</span><br>
                    <span class="brand-sub">Withdrawal Receipt</span>
                </div>
                <div class="doc-title">
                    <h1>Invoice</h1>
                    <div class="ref">Transaction #{{ $withdrawal->id }}</div>
                </div>
            </div>

            <!-- Info cards -->
            <div class="info-row">
                <div class="info-card">
                    <div class="label">From</div>
                    <div class="value">{{ $user->name ?? 'N/A' }}</div>
                    <div class="sub">{{ $user->email ?? 'N/A' }}</div>
                    <div class="sub">User ID: {{ $user->id ?? 'N/A' }}</div>
                </div>
                <div class="info-card">
                    <div class="label">Details</div>
                    <div class="sub">Date: {{ $withdrawal->created_at ? \Carbon\Carbon::parse($withdrawal->created_at)->format('M d, Y') : 'N/A' }}</div>
                    <div class="sub" style="margin-top:6px;">
                        Status:
                        <span class="badge badge-{{ $withdrawal->status ?? 'default' }}">
                            {{ ucfirst($withdrawal->status ?? 'Pending') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Withdrawal details -->
            <div class="section-title">Withdrawal Details</div>
            <table class="items">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Method</th>
                        <th class="right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="desc">Withdrawal Request</td>
                        <td class="cap">{{ str_replace('_', ' ', $withdrawal->method ?? 'bank_transfer') }}</td>
                        <td class="right">${{ number_format($withdrawal->amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Total -->
            <div class="total-row">
                <div class="spacer"></div>
                <div class="total-box">
                    <div class="t-label">Total Withdrawn</div>
                    <div class="t-amount">${{ number_format($withdrawal->amount, 2) }}</div>
                </div>
            </div>

            <!-- Bank details -->
            <div class="section-title">Bank Details</div>
            <table class="items">
                <thead>
                    <tr>
                        <th>Bank Name</th>
                        <th>Account Holder</th>
                        <th>Account Number</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $withdrawal->bank_name ?? 'N/A' }}</td>
                        <td>{{ $withdrawal->bank_account_holder ?? 'N/A' }}</td>
                        <td>{{ $withdrawal->bank_account_number ?? 'N/A' }}</td>
                        <td class="cap">{{ $withdrawal->account_type ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Rejection reason -->
            @if($withdrawal->rejection_reason)
            <div class="rejection">
                <div class="r-title">Rejection Reason</div>
                {{ $withdrawal->rejection_reason }}
            </div>
            @endif

            <!-- Footer -->
            <div class="footer">
                Generated on {{ \Carbon\Carbon::now()->format('M d, Y H:i') }} &middot; Thank you for using Nexora
            </div>

        </div>
    </div>
</body>
</html>
