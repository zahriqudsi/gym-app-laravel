<?php

namespace App\Filament\Widgets;

use App\Domain\Support\Money;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GymOverview extends StatsOverviewWidget
{
    // Cheap queries — render inline instead of a deferred Livewire request
    // (the single-process `php artisan serve` can stall on lazy widgets).
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $today = today();

        $collectedToday = (int) Payment::whereDate('paid_at', $today)
            ->where('status', 'completed')->sum('amount_cents');

        $outstanding = (int) Invoice::where('status', '!=', 'void')
            ->selectRaw('COALESCE(SUM(total_cents - amount_paid_cents), 0) as d')->value('d');

        $expiringSoon = Member::whereNotNull('current_expiry_on')
            ->whereBetween('current_expiry_on', [$today, $today->copy()->addDays(7)])
            ->count();

        return [
            Stat::make('Collected today', Money::format($collectedToday))
                ->description(Payment::whereDate('paid_at', $today)->count().' payments')
                ->color('success'),

            Stat::make('Active members', Member::where('status', 'active')->count())
                ->description(Member::where('status', 'due')->count().' with dues')
                ->color('primary'),

            Stat::make('Outstanding dues', Money::format($outstanding))
                ->color($outstanding > 0 ? 'warning' : 'gray'),

            Stat::make('Expiring in 7 days', $expiringSoon)
                ->description(Member::whereDate('current_expiry_on', $today)->count().' today')
                ->color($expiringSoon > 0 ? 'warning' : 'gray'),

            Stat::make('Check-ins today', Attendance::whereDate('checked_in_at', $today)->count())
                ->color('info'),
        ];
    }
}
