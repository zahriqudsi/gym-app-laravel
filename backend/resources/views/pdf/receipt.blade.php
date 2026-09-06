@php
    $sym = $gym['currency_symbol'];
    $money = fn ($c) => $sym.' '.number_format($c / 100, 2);
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
        .totals td { padding: 3px 8px; }
        .totals td.num { text-align: right; }
        .big { font-size: 14px; font-weight: bold; }
        .tag { display: inline-block; padding: 2px 8px; background: #ecfdf5; color: #047857; border-radius: 4px; font-weight: bold; }
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
                <div class="big">RECEIPT</div>
                <div>{{ $payment->number }}</div>
                <div class="muted">{{ $payment->paid_at->format('d M Y, g:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="row" style="margin-top: 20px;">
        <tr>
            <td>
                <div class="muted">Received from</div>
                <div class="big">{{ $payment->member?->name ?? 'Walk-in' }}</div>
                @if($payment->member)
                    <div class="muted">{{ $payment->member->member_no }} · {{ $payment->member->phone }}</div>
                @endif
            </td>
            <td style="text-align: right;">
                <div class="muted">Method</div>
                <div>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</div>
                @if($payment->reference)<div class="muted">Ref: {{ $payment->reference }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr><th>Applied to</th><th class="num">Amount</th></tr>
        </thead>
        <tbody>
            @forelse($payment->allocations as $alloc)
                <tr>
                    <td>Invoice {{ $alloc->invoice->number }}</td>
                    <td class="num">{{ $money($alloc->amount_cents) }}</td>
                </tr>
            @empty
                <tr><td class="muted">Unapplied / on account</td><td class="num">{{ $money($payment->amount_cents) }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="row totals" style="margin-top: 12px;">
        <tr>
            <td></td>
            <td style="width: 40%;">
                <table style="width: 100%;">
                    <tr><td>Amount paid</td><td class="num big">{{ $money($payment->amount_cents) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin-top: 24px;"><span class="tag">PAID</span></p>
    <p class="muted" style="margin-top: 40px;">Thank you. This is a computer-generated receipt.</p>
</body>
</html>
