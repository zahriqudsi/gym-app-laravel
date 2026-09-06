<?php

namespace App\Domain\Billing;

use App\Domain\Support\DocumentNumber;
use App\Domain\Support\Money;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Sell (or renew) a plan for a member: creates the membership record and
     * its invoice in one transaction. Does NOT take payment — call recordPayment().
     *
     * @param  array{starts_on?:string|Carbon, branch_id?:int, created_by?:int,
     *               price_cents?:int, discount_cents?:int, apply_signup_fee?:bool}  $opts
     */
    public function sellMembership(Member $member, Plan $plan, array $opts = []): Membership
    {
        return DB::transaction(function () use ($member, $plan, $opts) {
            $startsOn = isset($opts['starts_on']) ? Carbon::parse($opts['starts_on']) : today();

            // If renewing before the current membership ends, start the day after it.
            $currentEnd = $member->memberships()->where('status', 'active')->max('ends_on');
            if ($currentEnd && Carbon::parse($currentEnd)->gte($startsOn)) {
                $startsOn = Carbon::parse($currentEnd)->addDay();
            }

            $endsOn = $plan->billing_type === 'duration' && $plan->duration_days
                ? $startsOn->copy()->addDays($plan->duration_days)
                : null;

            $priceCents = $opts['price_cents'] ?? (int) $plan->price_cents;
            $isFirst = $member->memberships()->count() === 0;
            $applySignup = $opts['apply_signup_fee'] ?? $isFirst;

            $membership = Membership::create([
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'branch_id' => $opts['branch_id'] ?? $member->branch_id,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'sessions_total' => $plan->billing_type === 'sessions' ? $plan->session_count : null,
                'sessions_used' => 0,
                'price_cents' => $priceCents,
                'status' => 'active',
                'created_by' => $opts['created_by'] ?? auth()->id(),
            ]);

            $lines = [[
                'description' => $plan->name.' ('.$startsOn->toDateString().($endsOn ? ' – '.$endsOn->toDateString() : '').')',
                'qty' => 1,
                'unit_price_cents' => $priceCents,
                'tax_rate' => (float) $plan->tax_rate,
                'discount_cents' => $opts['discount_cents'] ?? 0,
                'itemable' => $membership,
            ]];

            if ($applySignup && $plan->signup_fee_cents > 0) {
                $lines[] = [
                    'description' => 'Registration fee',
                    'qty' => 1,
                    'unit_price_cents' => (int) $plan->signup_fee_cents,
                    'tax_rate' => (float) $plan->tax_rate,
                    'discount_cents' => 0,
                ];
            }

            $invoice = $this->createInvoice($member, $lines, [
                'branch_id' => $opts['branch_id'] ?? $member->branch_id,
                'created_by' => $opts['created_by'] ?? auth()->id(),
                'issued_on' => $startsOn,
                'due_on' => $startsOn,
            ]);

            $membership->update(['invoice_id' => $invoice->id]);

            return $membership->refresh();
        });
    }

    /**
     * @param  array<int, array{description:string, qty?:float, unit_price_cents:int,
     *                          tax_rate?:float, discount_cents?:int, itemable?:\Illuminate\Database\Eloquent\Model}>  $lines
     */
    public function createInvoice(?Member $member, array $lines, array $opts = []): Invoice
    {
        return DB::transaction(function () use ($member, $lines, $opts) {
            $inclusive = (bool) config('gym.tax.inclusive', true);

            $invoice = Invoice::create([
                'branch_id' => $opts['branch_id'] ?? $member?->branch_id,
                'member_id' => $member?->id,
                'number' => DocumentNumber::invoice(),
                'issued_on' => $opts['issued_on'] ?? today(),
                'due_on' => $opts['due_on'] ?? today(),
                'status' => 'unpaid',
                'notes' => $opts['notes'] ?? null,
                'created_by' => $opts['created_by'] ?? auth()->id(),
            ]);

            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            foreach ($lines as $line) {
                $qty = (float) ($line['qty'] ?? 1);
                $gross = (int) round($line['unit_price_cents'] * $qty) - (int) ($line['discount_cents'] ?? 0);
                $gross = max(0, $gross);
                $rate = (float) ($line['tax_rate'] ?? 0);

                if ($rate > 0 && $inclusive) {
                    ['net' => $net, 'tax' => $tax] = Money::splitInclusiveTax($gross, $rate);
                } elseif ($rate > 0) {
                    $net = $gross;
                    $tax = Money::addTax($net, $rate);
                } else {
                    $net = $gross;
                    $tax = 0;
                }

                $invoice->items()->create([
                    'description' => $line['description'],
                    'qty' => $qty,
                    'unit_price_cents' => $line['unit_price_cents'],
                    'tax_rate' => $rate,
                    'discount_cents' => $line['discount_cents'] ?? 0,
                    'line_total_cents' => $net + $tax,
                    'itemable_type' => isset($line['itemable']) ? $line['itemable']::class : null,
                    'itemable_id' => $line['itemable']->id ?? null,
                ]);

                $subtotal += $net;
                $taxTotal += $tax;
                $discountTotal += (int) ($line['discount_cents'] ?? 0);
            }

            $invoice->update([
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discountTotal,
                'tax_cents' => $taxTotal,
                'total_cents' => $subtotal + $taxTotal,
            ]);

            return $invoice->refresh();
        });
    }

    /**
     * Record a payment and (by default) allocate it to the member's oldest
     * unpaid invoices, oldest first.
     *
     * @param  array{amount_cents:int, method:string, paid_at?:string|Carbon, reference?:string,
     *               gateway?:string, gateway_ref?:string, branch_id?:int, created_by?:int,
     *               cash_session_id?:int, status?:string, allocate?:bool, invoice_id?:int, meta?:array}  $data
     */
    public function recordPayment(?Member $member, array $data): Payment
    {
        return DB::transaction(function () use ($member, $data) {
            $payment = Payment::create([
                'branch_id' => $data['branch_id'] ?? $member?->branch_id,
                'member_id' => $member?->id,
                'cash_session_id' => $data['cash_session_id'] ?? null,
                'number' => DocumentNumber::receipt(),
                'paid_at' => isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : now(),
                'method' => $data['method'],
                'amount_cents' => (int) $data['amount_cents'],
                'reference' => $data['reference'] ?? null,
                'gateway' => $data['gateway'] ?? null,
                'gateway_ref' => $data['gateway_ref'] ?? null,
                'status' => $data['status'] ?? 'completed',
                'created_by' => $data['created_by'] ?? auth()->id(),
                'meta' => $data['meta'] ?? null,
            ]);

            if (($data['allocate'] ?? true) && $payment->status === 'completed' && $member) {
                $target = isset($data['invoice_id'])
                    ? $member->invoices()->find($data['invoice_id'])
                    : null;
                $this->allocatePayment($payment, $target);
            }

            return $payment->refresh();
        });
    }

    public function allocatePayment(Payment $payment, ?Invoice $target = null): void
    {
        $remaining = $payment->unallocatedCents();
        if ($remaining <= 0 || ! $payment->member_id) {
            return;
        }

        $invoices = $target
            ? collect([$target])
            : Invoice::where('member_id', $payment->member_id)
                ->whereIn('status', ['unpaid', 'part_paid'])
                ->orderBy('issued_on')
                ->orderBy('id')
                ->get();

        foreach ($invoices as $invoice) {
            if ($remaining <= 0) {
                break;
            }

            $due = $invoice->balanceCents();
            if ($due <= 0) {
                continue;
            }

            $apply = min($due, $remaining);

            $allocation = $invoice->allocations()->firstOrCreate(
                ['payment_id' => $payment->id],
                ['amount_cents' => 0]
            );
            $allocation->increment('amount_cents', $apply);

            $invoice->increment('amount_paid_cents', $apply);
            $remaining -= $apply;

            $this->recalcInvoiceStatus($invoice->refresh());
        }
    }

    public function recalcInvoiceStatus(Invoice $invoice): void
    {
        if ($invoice->status === 'void') {
            return;
        }

        $status = match (true) {
            $invoice->amount_paid_cents <= 0 => 'unpaid',
            $invoice->amount_paid_cents < $invoice->total_cents => 'part_paid',
            default => 'paid',
        };

        $invoice->update(['status' => $status]);
    }

    public function memberDuesCents(Member $member): int
    {
        return (int) Invoice::where('member_id', $member->id)
            ->where('status', '!=', 'void')
            ->selectRaw('COALESCE(SUM(total_cents - amount_paid_cents), 0) AS due')
            ->value('due');
    }
}
