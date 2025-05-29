<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Order Summary (Admin)</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #ffffff;">
<div style="max-width: 700px; margin: auto; border: 1px solid #e0e0e0; padding: 20px;">
    <div style="text-align: center; background-color: #d32f2f; color: #ffffff; padding: 20px 0;">
        <h1 style="margin: 0;">New Order Notification</h1>
    </div>

    <div style="padding: 20px;">
        <h2 style="color: #d32f2f;">Order Details</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px; font-weight: bold;">Order ID:</td>
                <td style="padding: 8px;">#{{ $order->id }}</td>
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
                <td style="padding: 8px; font-weight: bold;">Status:</td>
                <td style="padding: 8px; text-transform: capitalize;">{{ $order->status }}</td>
            </tr>
        </table>

        <h3 style="color: #d32f2f; margin-top: 30px;">Buyer Information</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px; font-weight: bold;">Name:</td>
                <td style="padding: 8px;">{{ $order->buyer->first_name }} {{ $order->buyer->last_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Email:</td>
                <td style="padding: 8px;">{{ $order->buyer->email }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Location:</td>
                <td style="padding: 8px;">{{ $order->buyer->location }}</td>
            </tr>
        </table>

        <h3 style="color: #d32f2f; margin-top: 30px;">Seller Information</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px; font-weight: bold;">Name:</td>
                <td style="padding: 8px;">{{ $order->seller->first_name }} {{ $order->seller->last_name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Email:</td>
                <td style="padding: 8px;">{{ $order->seller->email }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Location:</td>
                <td style="padding: 8px;">{{ $order->seller->location }}</td>
            </tr>
        </table>

        <h3 style="color: #d32f2f; margin-top: 30px;">Financial Summary</h3>
        <table style="width: 100%; border-collapse: collapse;">
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
                <td style="padding: 8px; font-weight: bold;">Seller Payout:</td>
                <td style="padding: 8px;">PKR {{ number_format($order->total_seller_payout, 2) }}</td>
            </tr>
        </table>

        <div style="margin-top: 40px; text-align: center;">
            <a href="#" style="background-color: #d32f2f; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;">Review Order</a>
        </div>
    </div>

    <div style="text-align: center; font-size: 12px; color: #777; margin-top: 30px;">
        <p>&copy; {{ date('Y') }} Your Company. Admin Copy.</p>
    </div>
</div>
</body>
</html>
