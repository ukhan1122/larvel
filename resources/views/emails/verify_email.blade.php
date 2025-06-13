<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            color: #333333;
        }
        .email-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #eeeeee;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #f33e3e;
            color: #ffffff;
            text-align: center;
            padding: 20px;
        }
        .header img {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
        }
        .content h2 {
            color: #f33e3e;
            font-size: 20px;
            margin-top: 0;
        }
        .content p {
            font-size: 16px;
            line-height: 1.5;
        }
        .verify-button {
            display: block;
            text-align: center;
            margin: 20px 0;
        }
        .verify-button a {
            background-color: #f33e3e;
            color: #ffffff;
            text-decoration: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
        }
        .verify-button a:hover {
            background-color: #d12c2c;
        }
        .footer {
            background-color: #f9f9f9;
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #888888;
        }
        .footer a {
            color: #f33e3e;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="email-container">
    <!-- Header Section -->
    <div class="header">
        <img src="https://via.placeholder.com/150x50?text=Clossyyyy" alt="Clossyyyy Logo">
        <h1>Verify Your Email</h1>
    </div>

    <!-- Content Section -->
    <div class="content">
        <h2>Welcome to Clossyyyy!</h2>
        <p>Thanks for signing up! To get started, please verify your email address by clicking the button below.</p>

        <!-- Verify Button -->
        <div class="verify-button">
            <a href="{{ $url }}">Verify Email</a>
        </div>

        <p>If you didn’t sign up for a Clossyyyy account, you can safely ignore this email.</p>
    </div>

    <!-- Footer Section -->
    <div class="footer">
        <p>
            Need help? <a href="#">Contact Support</a><br>
            &copy; 2025 Clossyyyy. All rights reserved.
        </p>
    </div>
</div>
</body>
</html>
