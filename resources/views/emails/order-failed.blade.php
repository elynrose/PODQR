<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Cancelled and Refunded</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .order-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #6c757d;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Cancelled and Refunded</h1>
        <p>Dear {{ $order->user->name ?? 'Valued Customer' }},</p>
    </div>

    <div class="content">
        <div class="alert">
            <strong>Important:</strong> Your order has been cancelled and a full refund has been processed.
        </div>

        <p>We're writing to inform you that your order <strong>#{{ $order->order_number }}</strong> has been cancelled due to the following issue:</p>

        <div class="order-details">
            <h3>Order Details:</h3>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y') }}</p>
            <p><strong>Total Amount:</strong> ${{ number_format($order->total, 2) }}</p>
            <p><strong>Reason for Cancellation:</strong> {{ $reason }}</p>
        </div>

        <h3>What Happened?</h3>
        <p>{{ $reason }}</p>

        <h3>Refund Information:</h3>
        <p>A full refund of <strong>${{ number_format($order->total, 2) }}</strong> has been processed and will be credited back to your original payment method within 5-10 business days.</p>

        <h3>Next Steps:</h3>
        <p>To place a new order:</p>
        <ol>
            <li>Make sure to select a design when creating your order</li>
            <li>Choose your preferred products, sizes, and colors</li>
            <li>Complete the checkout process</li>
        </ol>

        <p style="text-align: center;">
            <a href="{{ route('dashboard') }}" class="btn">Create New Order</a>
        </p>

        <p>If you have any questions about this cancellation or need assistance placing a new order, please don't hesitate to contact our support team.</p>

        <p>Thank you for your understanding.</p>

        <p>Best regards,<br>
        The PODQR Team</p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>If you need assistance, please contact our support team through your account dashboard.</p>
    </div>
</body>
</html> 