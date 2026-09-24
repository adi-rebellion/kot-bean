<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 400px; margin: 0 auto; padding: 24px; color: #111; }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 2px dashed #ccc; padding-bottom: 16px; }
        .header h1 { margin: 0 0 4px; font-size: 20px; }
        .meta { font-size: 12px; color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        td { padding: 6px 0; vertical-align: top; }
        .totals { border-top: 2px solid #111; margin-top: 12px; padding-top: 12px; }
        .totals td { font-weight: 600; }
        .footer { text-align: center; margin-top: 24px; font-size: 11px; color: #888; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>{{ $order->restaurant->name }}</h1>
        @if($order->restaurant->address)<p style="font-size:12px;margin:4px 0">{{ $order->restaurant->address }}</p>@endif
        @if($order->restaurant->gstin)<p style="font-size:11px">GSTIN: {{ $order->restaurant->gstin }}</p>@endif
    </div>

    <div class="meta">
        <strong>Invoice:</strong> {{ $order->order_number }}<br>
        <strong>Date:</strong> {{ $order->created_at->format('d M Y, h:i A') }}<br>
        @if($order->table)<strong>Table:</strong> {{ $order->table->name }}<br>@endif
    </div>

    <table>
        @foreach($order->items as $item)
        <tr>
            <td>{{ $item->product_name }}<br><small>{{ $item->quantity }} × ₹{{ number_format($item->unit_price, 2) }}</small></td>
            <td style="text-align:right">₹{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td style="text-align:right">₹{{ number_format($order->subtotal, 2) }}</td></tr>
        <tr><td>Tax</td><td style="text-align:right">₹{{ number_format($order->tax_amount, 2) }}</td></tr>
        @if($order->discount_amount > 0)
        <tr>
            <td>
                Discount
                @if($order->promotion)
                    ({{ $order->promotion->name }})
                @endif
            </td>
            <td style="text-align:right">-₹{{ number_format($order->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr><td>TOTAL</td><td style="text-align:right">₹{{ number_format($order->total, 2) }}</td></tr>
    </table>

    @if($order->payments->isNotEmpty())
    <p style="font-size:12px;margin-top:16px">
        <strong>Payment:</strong> {{ $order->payments->first()->method->label() }}
        @if($order->payments->first()->reference) ({{ $order->payments->first()->reference }}) @endif
    </p>
    @endif

    <div class="footer">Thank you for dining with us!</div>
</body>
</html>
