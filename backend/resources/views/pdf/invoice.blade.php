@php
    $sym = $gym['currency_symbol'];
    $money = fn ($c) => $sym.' '.number_format($c / 100, 2);
    $statusColors = ['paid' => '#047857', 'part_paid' => '#b45309', 'unpaid' => '#b91c1c', 'void' => '#6b7280'];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #1f2937; margin: 32px; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #6b7280; }
        .row { width: 100%; }
        .row td { vertical-align: top; padding: 2px 0; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.lines th, table.lines td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        table.lines td.num, table.lines th.num { text-align: right; }
        .totals { width: 45%; margin-left: 55%; margin-top: 10px; }
        .totals td { padding: 3px 8px; }
        .totals td.num { text-align: right; }
        .big { font-size: 14px; font-weight: bold; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 4px; color: #fff; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>
    <table class="row">
        <tr>
            <td>
                <h1>{{ $gym['name'] }}</h1>
                <div class="muted">
                    {{ $gym['branch'] }}@if($gym['address']) — {{ $gym['address'] }}@endif<br>
                    @if($gym['phone']) {{ $gym['phone'] }} @endif
                </div>
            </td>
            <td style="text-align: right;">
                <div class="big">INVOICE</div>
                <div>{{ $invoice->number }}</div>
                <div class="muted">Issued {{ $invoice->issued_on->format('d M Y') }}</div>
                @if($invoice->due_on)<div class="muted">Due {{ $invoice->due_on->format('d M Y') }}</div>@endif
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px;">
        <div class="muted">Bill to</div>
        <div class="big">{{ $invoice->member?->name ?? '—' }}</div>
        @if($invoice->member)
            <div class="muted">{{ $invoice->member->member_no }} · {{ $invoice->member->phone }}</div>
        @endif
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Qty</th>
                <th class="num">Unit</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($item->qty, 2), '0'), '.') }}</td>
                    <td class="num">{{ $money($item->unit_price_cents) }}</td>
                    <td class="num">{{ $money($item->line_total_cents) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">{{ $money($invoice->subtotal_cents) }}</td></tr>
        @if($invoice->discount_cents > 0)
            <tr><td>Discount</td><td class="num">− {{ $money($invoice->discount_cents) }}</td></tr>
        @endif
        @if($invoice->tax_cents > 0)
            <tr><td>{{ $gym['tax_label'] }}</td><td class="num">{{ $money($invoice->tax_cents) }}</td></tr>
        @endif
        <tr><td class="big">Total</td><td class="num big">{{ $money($invoice->total_cents) }}</td></tr>
        <tr><td>Paid</td><td class="num">{{ $money($invoice->amount_paid_cents) }}</td></tr>
        <tr><td class="big">Balance</td><td class="num big">{{ $money($invoice->total_cents - $invoice->amount_paid_cents) }}</td></tr>
    </table>

    <p style="margin-top: 24px;">
        <span class="status" style="background: {{ $statusColors[$invoice->status] ?? '#6b7280' }};">{{ str_replace('_', ' ', $invoice->status) }}</span>
    </p>
</body>
</html>
