<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $order->id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=VT323&display=swap');
        body {
            font-family: 'VT323', monospace;
            line-height: 1.4;
            color: #000;
            background-color: #fdfdfd;
            max-width: 320px; /* Typical thermal printer width */
            margin: 0 auto;
            padding: 20px;
        }
        .receipt {
            background-color: #fff;
            border: 1px dashed #ccc;
            padding: 15px;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
        }
        .receipt-header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .receipt-header p {
            margin: 2px 0;
            font-size: 14px;
        }
        .receipt-body {
            margin-bottom: 15px;
        }
        .receipt-section {
            margin-bottom: 10px;
        }
        .receipt-section h2 {
            font-size: 16px;
            margin: 15px 0 5px;
            text-transform: uppercase;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            text-align: center;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 14px;
        }
        .receipt-table th, .receipt-table td {
            padding: 5px 2px;
            text-align: left;
        }
        .receipt-table th {
            border-bottom: 1px dashed #000;
        }
        .receipt-table .item-row td {
            padding-bottom: 10px;
        }
        .receipt-table .price {
            text-align: right;
        }
        .totals {
            margin-top: 15px;
            border-top: 1px solid #000;
        }
        .totals .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 16px;
        }
        .totals .total-row.grand-total {
            font-size: 20px;
            font-weight: bold;
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 5px;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .print-button {
            background-color: #333;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px 0;
            font-family: 'Arial', sans-serif;
        }
        .print-button:hover {
            background-color: #555;
        }
        .back-button {
            background-color: #f0f0f0;
            color: #333;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px 0;
            text-decoration: none;
            font-family: 'Arial', sans-serif;
            display: inline-block;
        }
        .back-button:hover {
            background-color: #e0e0e0;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
                background-color: #fff;
            }
            .receipt {
                border: none;
                max-width: 100%;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <h1>MW Waters</h1>
            <p>123 Main Street, Your City</p>
            <p>Tel: (123) 456-7890</p>
        </div>

        <div class="receipt-body">
            <div class="receipt-section">
                <p>Receipt #: {{ $order->id }}</p>
                <p>Date: {{ $order->created_at->format('m/d/Y H:i') }}</p>
                <p>Cashier: {{ Auth::user()->name }}</p>
                <p>Status: {{ ucfirst($order->status) }}</p>
            </div>

            <div class="receipt-section">
                @if($order->isWalkInSale())
                    <p>Customer: {{ $order->customer_name ?? 'Walk-in Customer' }}</p>
                @else
                    <p>Customer: {{ $order->customer->fullname }}</p>
                @endif
                <p>Type: {{ $order->order_type }}</p>
            </div>

            <div class="receipt-section">
                <h2>Order Items</h2>
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th class="price">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr class="item-row">
                                <td>{{ $item->product->name }} ({{ ucfirst($item->refill_status) }})</td>
                                <td>{{ $item->quantity }}</td>
                                <td class="price">P{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>P{{ number_format($order->total_amount, 2) }}</span>
                </div>
                <div class="total-row">
                    <span>Tax (0%)</span>
                    <span>P0.00</span>
                </div>
                <div class="total-row grand-total">
                    <span>Total</span>
                    <span>P{{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
             @if($order->notes)
                <div class="receipt-section">
                    <h2>Notes</h2>
                    <p>{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        <div class="receipt-footer">
            <p>Thank you for your purchase!</p>
            <p>Water you waiting for? Come again!</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center;">
        <button class="print-button" onclick="window.print()">Print Receipt</button>
        <a href="{{ route('admin.sales.index') }}" class="back-button">Back to Sales</a>
    </div>
</body>
</html> 
