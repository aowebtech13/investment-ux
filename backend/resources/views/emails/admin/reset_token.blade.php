<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Token</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; text-align: center;">
    <h2>Admin Password Reset</h2>
    <p>You requested a password reset for your Nexora admin account.</p>
    <p>Your 6-digit verification token is:</p>
    <div style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #007bff; padding: 20px; background: #f0f7ff; display: inline-block; border-radius: 10px; margin: 20px 0;">
        {{ $token }}
    </div>
    <p>This token will expire in 15 minutes.</p>
    <p>If you did not request this, please ignore this email.</p>
    <p>Regards,<br>Nexora Finance Security Team</p>
</body>
</html>
