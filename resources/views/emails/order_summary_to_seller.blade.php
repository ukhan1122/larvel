<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Summary</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #ffffff;">
<div style="max-width: 600px; margin: auto; border: 1px solid #e0e0e0; padding: 20px;">
    <div style="text-align: center; background-color: #d32f2f; color: #ffffff; padding: 20px 0;">
        <h1 style="margin: 0;">Order Summary</h1>
    </div>

    <div style="padding: 20px;">
        <h2 style="color: #d32f2f;">Hello {{ $order->seller->first_name }},</h2>
        <p>You have received a new order. Here are the details:</p>

        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <tr>
                <td style="padding: 8px; font-weight: bold;">Buyer Name:</td>
                <td style="padding: 8px;">{{ $order->buyer->first_name }} {{ $order->buyer->last_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Buyer Email:</td>
                <td style="padding: 8px;">{{ $order->buyer->email }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Tracking No:</td>
                <td style="padding: 8px;">{{ $order->tracking_no }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Expected Delivery:</td>
                <td style="padding: 8px;">{{ \Carbon\Carbon::parse($order->expected_delivery_date)->toFormattedDateString() }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Order Status:</td>
                <td style="padding: 8px; text-transform: capitalize;">{{ $order->status }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Subtotal:</td>
                <td style="padding: 8px;">PKR {{ number_format($order->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Delivery Fee:</td>
                <td style="padding: 8px;">PKR {{ number_format($order->delivery_fee, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Platform Fee:</td>
                <td style="padding: 8px;">PKR {{ number_format($order->platform_fee, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Total Amount:</td>
                <td style="padding: 8px;">PKR {{ number_format($order->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Total Seller Payout:</td>
                <td style="padding: 8px;">PKR {{ number_format($order->total_seller_payout, 2) }}</td>
            </tr>
        </table>

        <p style="margin-top: 30px;">Please ensure timely delivery and communication with the buyer.</p>

        <div style="margin-top: 40px; text-align: center;">
            <a href="#" style="background-color: #d32f2f; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;">View Order</a>
        </div>
    </div>

    <div style="text-align: center; font-size: 12px; color: #777; margin-top: 30px;">
        <p>&copy; {{ date('Y') }} Your Company. All rights reserved.</p>
    </div>
</div>
</body>
</html>
