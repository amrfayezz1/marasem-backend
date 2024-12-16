<!DOCTYPE html>
<html>

<head>
    <title>Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }

        .invoice-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .invoice-details {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .total {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="invoice-header">
        <h1>Invoice #{{ $invoice->invoice_number }}</h1>
        <p>Date: {{ $invoice->created_at->format('Y-m-d') }}</p>
    </div>

    <div class="invoice-details">
        <h3>Order Details</h3>
        <p><strong>Order ID:</strong> {{ $order->id }}</p>
        <p><strong>Customer:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
        <p><strong>Address:</strong> {{ $order->address->address }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Size</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cartItems as $item)
                <tr>
                    <td>{{ $item->artwork->name }}</td>
                    <td>{{ $item->size }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price, 2) }}</td>
                    <td>{{ number_format($item->price * $item->quantity, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" class="total">Grand Total</td>
                <td class="total">{{ number_format($invoice->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>