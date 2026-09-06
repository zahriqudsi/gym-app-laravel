<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Collections — last 30 days';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = today()->subDays(29);

        $rows = Payment::query()
            ->where('status', 'completed')
            ->whereDate('paid_at', '>=', $start)
            ->selectRaw('DATE(paid_at) as d, SUM(amount_cents) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $labels = [];
        $data = [];
        for ($i = 0; $i < 30; $i++) {
            $day = $start->copy()->addDays($i);
            $labels[] = $day->format('d M');
            $data[] = round((int) ($rows[$day->toDateString()] ?? 0) / 100, 2);
        }

        return [
            'datasets' => [[
                'label' => 'Collected (Rs)',
                'data' => $data,
                'borderColor' => '#2563eb',
                'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
