<?php

namespace App\Filament\Pages\Reports;

use App\Models\Invoice;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class DuesAgeing extends Page
{
    protected string $view = 'filament.pages.reports.dues-ageing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $title = 'Dues ageing';

    /** @return array{buckets: array<string,int>, total: int, debtors: Collection} */
    public function getData(): array
    {
        $open = Invoice::query()
            ->where('status', '!=', 'void')
            ->whereColumn('amount_paid_cents', '<', 'total_cents')
            ->with('member')
            ->get();

        $buckets = ['0–30' => 0, '31–60' => 0, '61–90' => 0, '90+' => 0];

        foreach ($open as $inv) {
            $age = $inv->issued_on->diffInDays(today());
            $bal = $inv->balanceCents();
            $key = match (true) {
                $age <= 30 => '0–30',
                $age <= 60 => '31–60',
                $age <= 90 => '61–90',
                default => '90+',
            };
            $buckets[$key] += $bal;
        }

        $debtors = $open->groupBy('member_id')->map(function ($invoices) {
            $member = $invoices->first()->member;

            return [
                'member' => $member,
                'balance' => $invoices->sum(fn ($i) => $i->balanceCents()),
                'invoices' => $invoices->count(),
                'oldest_days' => $invoices->max(fn ($i) => $i->issued_on->diffInDays(today())),
            ];
        })->sortByDesc('balance')->values();

        return [
            'buckets' => $buckets,
            'total' => array_sum($buckets),
            'debtors' => $debtors,
        ];
    }
}
