<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Code</title>
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
        .lock-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 28px;
            font-weight: 700;
            margin: 20px 0;
            color: white;
        }
        .subtitle {
            font-size: 16px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        .otp-box {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
            backdrop-filter: blur(10px);
        }
        .otp-code {
            font-size: 42px;
            font-weight: 700;
            letter-spacing: 6px;
            color: white;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        .info-text {
            font-size: 14px;
            margin-top: 20px;
            opacity: 0.85;
        }
        .warning {
            background: rgba(255, 107, 107, 0.2);
            border-left: 4px solid #ff6b6b;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            text-align: left;
            font-size: 13px;
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
        <div class="lock-icon">🔐</div>
        <h1 class="title">Password Reset</h1>
        <p class="subtitle">You requested to reset your Nexora Finance password</p>

        <p style="font-size: 15px; margin-bottom: 10px;">Your 7-digit verification code is:</p>
        
        <div class="otp-box">
            <div class="otp-code">{{ $otp }}</div>
        </div>

        <div class="info-text">
            ⏱️ This code will expire in <strong>5 minutes</strong>
        </div>

        <div class="warning">
            <strong>⚠️ Security Notice:</strong> Never share this code with anyone. Nexora team will never ask for this code via email or phone.
        </div>

        <p style="font-size: 14px; margin-top: 25px;">
            If you did not request a password reset, please ignore this email or contact our support team immediately.
        </p>

        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Nexora Finance. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
