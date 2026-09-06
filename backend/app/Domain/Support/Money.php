<?php

namespace App\Domain\Support;

use NumberFormatter;

/**
 * All money in this app is stored as integer cents (LKR has 100 cents).
 * Never use floats for money math.
 */
final class Money
{
    public static function toCents(int|float|string $rupees): int
    {
        return (int) round(((float) $rupees) * 100);
    }

    public static function toRupees(int $cents): float
    {
        return $cents / 100;
    }

    public static function format(int $cents, bool $withSymbol = true): string
    {
        $symbol = config('gym.currency_symbol', 'Rs');
        $formatted = number_format($cents / 100, 2);

        return $withSymbol ? "{$symbol} {$formatted}" : $formatted;
    }

    /** Split a tax-inclusive gross amount into net + tax components. */
    public static function splitInclusiveTax(int $grossCents, float $ratePercent): array
    {
        if ($ratePercent <= 0) {
            return ['net' => $grossCents, 'tax' => 0];
        }

        $net = (int) round($grossCents / (1 + $ratePercent / 100));

        return ['net' => $net, 'tax' => $grossCents - $net];
    }

    /** Add tax on top of a net amount. */
    public static function addTax(int $netCents, float $ratePercent): int
    {
        return (int) round($netCents * $ratePercent / 100);
    }
}
