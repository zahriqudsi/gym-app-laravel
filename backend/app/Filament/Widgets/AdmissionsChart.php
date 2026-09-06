<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Membership;
use Filament\Widgets\ChartWidget;

class AdmissionsChart extends ChartWidget
{
    protected ?string $heading = 'New members vs renewals — last 12 weeks';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = today()->startOfWeek()->subWeeks(11);

        $labels = [];
        $joined = [];
        $renewed = [];

        for ($i = 0; $i < 12; $i++) {
            $from = $start->copy()->addWeeks($i);
            $to = $from->copy()->addWeek();
            $labels[] = $from->format('d M');

            $joined[] = Member::whereBetween('joined_on', [$from, $to])->count();

            // renewals = memberships created for members who already had one
            $renewed[] = Membership::whereBetween('created_at', [$from, $to])
                ->whereIn('member_id', function ($q) use ($from) {
                    $q->select('member_id')->from('memberships')->where('created_at', '<', $from);
                })
                ->count();
        }

        return [
            'datasets' => [
                ['label' => 'New members', 'data' => $joined, 'backgroundColor' => '#16a34a'],
                ['label' => 'Renewals', 'data' => $renewed, 'backgroundColor' => '#2563eb'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
