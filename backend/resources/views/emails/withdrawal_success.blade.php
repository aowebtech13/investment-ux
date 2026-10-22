<!DOCTYPE html>
<html>
<head>
    <title>Withdrawal Request Submitted</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .celebration-icon {
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
            color: #059669;
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
    <div class="container">
        <div class="celebration-icon">🎉</div>
        <h1 class="title">Congratulations!</h1>
        <p class="subtitle">Hello {{ $user->name }}, your withdrawal request has been submitted successfully!</p>

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
                    <td><strong>{{ ucfirst($withdrawal->status) }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="info-text">
            Your withdrawal request is being reviewed by our team. You will receive another email once the withdrawal is processed and approved.
        </div>

        <a href="{{ config('app.url') }}/transaction" class="button">View Transaction History</a>

        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Nexora Finance. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
