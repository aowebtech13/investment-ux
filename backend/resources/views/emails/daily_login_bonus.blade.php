<!DOCTYPE html>
<html>
<head>
    <title>Daily Login Bonus</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .gift-icon {
            font-size: 48px;
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
            color: #667eea;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="gift-icon">🎁</div>
        <h1 class="title">Daily Login Bonus!</h1>
        <p class="subtitle">Hello {{ $user->name }}, thanks for logging in today!</p>

        <div class="amount-box">
            <div class="amount">+${{ number_format($amount, 2) }}</div>
            <div class="balance-text">Your new balance is <strong>${{ number_format($balance, 2) }}</strong></div>
        </div>

        <div class="info-text">
            💡 Come back tomorrow and log in again to earn another daily bonus!
        </div>

        <a href="{{ config('app.url') }}/dashboard" class="button">View Dashboard</a>

        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Nexora Finance. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
