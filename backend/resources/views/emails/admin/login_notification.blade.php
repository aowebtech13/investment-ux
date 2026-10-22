<!DOCTYPE html>
<html>
<head>
    <title>Login Notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">
    <h2>Security Alert: New Admin Login</h2>
    <p>Hello {{ $user->name }},</p>
    <p>This is to notify you that your Nexora Finance admin account was just logged into.</p>
    <div style="background: #f4f4f4; padding: 15px; border-radius: 5px;">
        <p><strong>Time:</strong> {{ $time }}</p>
        <p><strong>IP Address:</strong> {{ $ip }}</p>
    </div>
    <p>If this was not you, please reset your password immediately using the 6-digit token method.</p>
    <p>To reset your password, visit: <a href="{{ config('app.url') }}/admin/password/reset">Reset Password</a></p>
    <p>Regards,<br>Nexora Finance Security Team</p>
</body>
</html>
