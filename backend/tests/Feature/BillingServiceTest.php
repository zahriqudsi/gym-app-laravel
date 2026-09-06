<?php

namespace Tests\Feature;

use App\Domain\Billing\BillingService;
use App\Models\Member;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function plan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Monthly',
            'category' => 'gym',
            'billing_type' => 'duration',
            'duration_days' => 30,
            'price_cents' => 500000,
            'signup_fee_cents' => 100000,
            'tax_rate' => 0,
            'grace_days' => 3,
        ], $overrides));
    }

    private function member(): Member
    {
        return Member::create([
            'member_no' => 'M0001',
            'first_name' => 'Test',
            'last_name' => 'Member',
            'phone' => '0770000000',
            'joined_on' => now(),
        ]);
    }

    public function test_selling_a_membership_creates_a_dated_membership_and_invoice(): void
    {
        $billing = app(BillingService::class);
        $member = $this->member();

        $membership = $billing->sellMembership($member, $this->plan(), ['starts_on' => '2026-01-01']);

        $this->assertSame('2026-01-01', $membership->starts_on->toDateString());
        $this->assertSame('2026-01-31', $membership->ends_on->toDateString());
        $this->assertNotNull($membership->invoice_id);

        // plan 5,000 + signup 1,000 (first membership) => 6,000.00
        $this->assertSame(600000, $membership->invoice->total_cents);
        $this->assertSame(600000, $billing->memberDuesCents($member));
    }

    public function test_partial_then_full_payment_allocates_and_clears_dues(): void
    {
        $billing = app(BillingService::class);
        $member = $this->member();
        $billing->sellMembership($member, $this->plan(), ['starts_on' => '2026-01-01']);

        $billing->recordPayment($member, ['amount_cents' => 250000, 'method' => 'cash']);
        $this->assertSame(350000, $billing->memberDuesCents($member));

        $invoice = $member->invoices()->first();
        $this->assertSame('part_paid', $invoice->refresh()->status);

        $billing->recordPayment($member, ['amount_cents' => 350000, 'method' => 'card']);
        $this->assertSame(0, $billing->memberDuesCents($member));
        $this->assertSame('paid', $invoice->refresh()->status);
    }

    public function test_renewal_before_expiry_starts_after_the_current_term(): void
    {
        $billing = app(BillingService::class);
        $member = $this->member();
        $plan = $this->plan();

        $first = $billing->sellMembership($member, $plan, ['starts_on' => '2026-01-01']);
        $second = $billing->sellMembership($member, $plan, ['starts_on' => '2026-01-15']);

        $this->assertSame($first->ends_on->copy()->addDay()->toDateString(), $second->starts_on->toDateString());
    }

    public function test_inclusive_tax_is_split_out_of_the_price(): void
    {
        config()->set('gym.tax.inclusive', true);
        $billing = app(BillingService::class);
        $member = $this->member();

        $membership = $billing->sellMembership($member, $this->plan([
            'tax_rate' => 18, 'signup_fee_cents' => 0,
        ]));

        $invoice = $membership->invoice;
        $this->assertSame(500000, $invoice->total_cents);           // gross unchanged
        $this->assertSame(423729, $invoice->subtotal_cents);        // net
        $this->assertSame(76271, $invoice->tax_cents);              // tax component
    }
}
