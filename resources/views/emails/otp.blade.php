<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset OTP</title>
</head>
<body>
    <p>Hello {{ $name }},</p>
    <p>Your one-time password (OTP) to reset your ASCC-IT account password is:</p>
    <h2 style="letter-spacing:6px;">{{ $otp }}</h2>
    <p>This code will expire in 10 minutes. If you did not request a password reset, you can safely ignore this email.</p>
    <p>Regards,<br>ASCC-IT Support</p>
</body>
</html>
