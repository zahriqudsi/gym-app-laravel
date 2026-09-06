<?php

namespace App\Domain\Support;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Carbon;

/**
 * Sequential, human-readable document numbers.
 *
 * MVP implementation: derive the next sequence from a count of this year's rows.
 * It is good enough for a single gym with one front desk. If you ever run
 * concurrent front desks, replace this with a dedicated `number_sequences`
 * table updated inside a row lock (see docs/BUILD_PLAN.md).
 */
final class DocumentNumber
{
    public static function member(): string
    {
        $prefix = config('gym.numbering.member_prefix', 'M');
        $seq = Member::withTrashed()->count() + 1;

        return sprintf('%s%05d', $prefix, $seq);
    }

    public static function invoice(?Carbon $date = null): string
    {
        $date ??= now();
        $prefix = config('gym.numbering.invoice_prefix', 'INV');
        $seq = Invoice::whereYear('created_at', $date->year)->count() + 1;

        return sprintf('%s-%d-%05d', $prefix, $date->year, $seq);
    }

    public static function receipt(?Carbon $date = null): string
    {
        $date ??= now();
        $prefix = config('gym.numbering.receipt_prefix', 'RCP');
        $seq = Payment::whereYear('created_at', $date->year)->count() + 1;

        return sprintf('%s-%d-%05d', $prefix, $date->year, $seq);
    }
}
