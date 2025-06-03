<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Product Status Update</title>
</head>
<body style="background-color: #ffffff; color: #333; font-family: Arial, sans-serif; padding: 20px;">
<div style="border: 1px solid #e3342f; border-radius: 8px; padding: 20px;">
    <h2 style="color: #e3342f;">Product Status Update</h2>
    <p>Hello {{ $product->user->first_name }},</p>
    <p>Your product <strong>{{ $product->title }}</strong> has been <strong>{{ strtoupper($product->approval_status) }}</strong>.</p>
    <p>Thank you for using our platform.</p>
</div>
</body>
</html>
