<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Closyyyy Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .email-header {
            background-color: #ff0000;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }
        .email-body {
            padding: 20px;
        }
        .email-body h2 {
            color: #ff0000;
            font-size: 20px;
        }
        .email-body p {
            font-size: 16px;
            line-height: 1.5;
            margin: 10px 0;
        }
        .email-button {
            display: block;
            width: fit-content;
            background-color: #ff0000;
            color: #ffffff;
            padding: 12px 20px;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            margin: 20px 0;
        }
        .email-footer {
            background-color: #f9f9f9;
            color: #999999;
            padding: 15px;
            text-align: center;
            font-size: 14px;
        }
        .email-footer a {
            color: #ff0000;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="email-container">
    <!-- Header -->
    <div class="email-header">
        Clossyyyy
    </div>

    <!-- Body -->
    <div class="email-body">
        <h2>Reset Your Password</h2>
        <p>Hi there,</p>
        <p>You recently requested to reset your Clossyyyy password. Click the button below to reset it:</p>
        <a href="{{ $url }}" class="email-button">Reset Password</a>
        <p>If you did not request a password reset, please ignore this email. This link will expire in 60 minutes.</p>
        <p>Thank you for being part of the Clossyyyy community!</p>
    </div>

    <!-- Footer -->
    <div class="email-footer">
        <p>&copy; {{ date('Y') }} Clossyyyy. All rights reserved.</p>
        <p><a href="#">Terms of Service</a> | <a href="#">Privacy Policy</a></p>
    </div>
</div>
</body>
</html>
