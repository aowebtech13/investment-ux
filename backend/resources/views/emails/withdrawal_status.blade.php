<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Withdrawal Request {{ ucfirst($status) }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .approved {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .rejected {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .status-icon {
            font-size: 56px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 26px;
            font-weight: 700;
            margin: 20px 0;
            color: white;
        }
        .subtitle {
            font-size: 16px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        .amount-box {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
            backdrop-filter: blur(10px);
        }
        .amount {
            font-size: 42px;
            font-weight: 700;
            color: white;
        }
        .balance-text {
            font-size: 15px;
            margin-top: 15px;
            opacity: 0.95;
        }
        .info-text {
            font-size: 14px;
            margin-top: 20px;
            opacity: 0.85;
        }
        .rejection-box {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .rejection-box strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            opacity: 0.9;
        }
        .rejection-box p {
            margin: 0;
            font-size: 14px;
            opacity: 0.95;
        }
        .footer {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: 30px;
            padding-top: 20px;
            font-size: 12px;
            opacity: 0.8;
        }
        .button {
            display: inline-block;
            background: white;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        .details-table {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .details-table td {
            padding: 8px 12px;
            font-size: 14px;
        }
        .details-table td:first-child {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container {{ $status === 'approved' ? 'approved' : 'rejected' }}">
        <div class="status-icon">{{ $status === 'approved' ? '✅' : '❌' }}</div>
        <h1 class="title">{{ $status === 'approved' ? 'Withdrawal Approved!' : 'Withdrawal Rejected' }}</h1>
        <p class="subtitle">Hello {{ $user->name }}, your withdrawal request has been {{ $status }}.</p>

        <div class="amount-box">
            <div class="amount">${{ number_format($withdrawal->amount, 2) }}</div>
            <div class="balance-text">Request ID: #{{ $withdrawal->id }}</div>
        </div>

        <div class="details-table">
            <table width="100%">
                <tr>
                    <td><strong>Bank:</strong></td>
                    <td>{{ $withdrawal->bank_name ?? $withdrawal->method }}</td>
                </tr>
                <tr>
                    <td><strong>Account Holder:</strong></td>
                    <td>{{ $withdrawal->bank_account_holder }}</td>
                </tr>
                <tr>
                    <td><strong>Account Number:</strong></td>
                    <td>{{ $withdrawal->bank_account_number }}</td>
                </tr>
                <tr>
                    <td><strong>Account Type:</strong></td>
                    <td>{{ ucfirst($withdrawal->account_type ?? 'checking') }}</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td><strong>{{ ucfirst($status) }}</strong></td>
                </tr>
            </table>
        </div>

        @if($status === 'approved')
            <div class="info-text">
                Your withdrawal of ${{ number_format($withdrawal->amount, 2) }} has been approved and is being processed. The funds will be transferred to your bank account shortly.
            </div>
        @else
            <div class="rejection-box">
                <strong>Reason for Rejection:</strong>
                <p>{{ $rejectionReason ?? 'No reason provided.' }}</p>
            </div>
            <div class="info-text">
                The refunded amount of ${{ number_format($withdrawal->amount, 2) }} has been returned to your available balance. You can submit a new withdrawal request at any time.
            </div>
        @endif

        <a href="{{ config('app.url') }}/transaction" class="button">View Transaction History</a>

        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Nexora Finance. All rights reserved.</p>
        </div>
    </div>
</body>
</html>