<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>KOT {{ $kot->kot_number }}</title>
    <style>
        body { font-family: monospace; max-width: 300px; margin: 0 auto; padding: 16px; }
        h1 { font-size: 18px; margin: 0 0 8px; text-align: center; }
        .meta { text-align: center; font-size: 14px; margin-bottom: 16px; border-bottom: 1px dashed #000; padding-bottom: 8px; }
        .item { font-size: 16px; margin: 8px 0; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body onload="window.print()">
    <h1>KOT #{{ $kot->kot_number }}</h1>
    <div class="meta">
        @if($kot->table) TABLE {{ strtoupper($kot->table->name) }}<br>@endif
        {{ $kot->created_at->format('h:i A') }}
    </div>
    @foreach($kot->items as $item)
        <div class="item"><strong>{{ $item->quantity }}×</strong> {{ $item->product_name }}</div>
        @if($item->notes)<div style="font-size:12px;padding-left:16px">{{ $item->notes }}</div>@endif
    @endforeach
</body>
</html>
