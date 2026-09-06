@php
    $data = $this->getData();
    $fmt = fn ($c) => 'Rs '.number_format($c / 100, 2);
@endphp

<x-filament-panels::page>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;">
        @foreach ($data['buckets'] as $label => $amount)
            <x-filament::section>
                <div style="font-size:.8rem;opacity:.7;">{{ $label }} days</div>
                <div style="font-size:1.5rem;font-weight:600;margin-top:.25rem;{{ $label === '90+' && $amount > 0 ? 'color:#dc2626;' : '' }}">
                    {{ $fmt($amount) }}
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <div style="text-align:right;font-size:.85rem;opacity:.7;">
        Total outstanding: <strong>{{ $fmt($data['total']) }}</strong> · {{ $data['debtors']->count() }} members
    </div>

    <x-filament::section heading="Members with a balance">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                <thead>
                    <tr style="text-align:left;opacity:.6;border-bottom:1px solid rgba(128,128,128,.3);">
                        <th style="padding:.5rem .75rem;">Member</th>
                        <th style="padding:.5rem .75rem;">Phone</th>
                        <th style="padding:.5rem .75rem;text-align:right;">Invoices</th>
                        <th style="padding:.5rem .75rem;text-align:right;">Oldest</th>
                        <th style="padding:.5rem .75rem;text-align:right;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['debtors'] as $row)
                        <tr style="border-bottom:1px solid rgba(128,128,128,.15);">
                            <td style="padding:.5rem .75rem;">
                                <a href="{{ route('filament.admin.resources.members.edit', $row['member']) }}"
                                   style="color:#2563eb;font-weight:500;">{{ $row['member']->name }}</a>
                                <span style="opacity:.5;">{{ $row['member']->member_no }}</span>
                            </td>
                            <td style="padding:.5rem .75rem;opacity:.7;">{{ $row['member']->phone }}</td>
                            <td style="padding:.5rem .75rem;text-align:right;">{{ $row['invoices'] }}</td>
                            <td style="padding:.5rem .75rem;text-align:right;">{{ $row['oldest_days'] }}d</td>
                            <td style="padding:.5rem .75rem;text-align:right;font-weight:600;">{{ $fmt($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:1.5rem;text-align:center;opacity:.6;">No outstanding balances</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
