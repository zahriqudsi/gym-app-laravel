<?php

namespace Tests\Feature;

use App\Domain\Billing\BillingService;
use App\Domain\Members\MembershipStatusService;
use App\Models\Member;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MembershipAccessTest extends TestCase
{
    use RefreshDatabase;

    private BillingService $billing;

    private MembershipStatusService $status;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billing = app(BillingService::class);
        $this->status = app(MembershipStatusService::class);
    }

    private function memberWithPlan(int $graceDays = 3): array
    {
        $plan = Plan::create([
            'name' => 'Monthly', 'category' => 'gym', 'billing_type' => 'duration',
            'duration_days' => 30, 'price_cents' => 500000, 'signup_fee_cents' => 0,
            'tax_rate' => 0, 'grace_days' => $graceDays,
        ]);

        $member = Member::create([
            'member_no' => 'M1', 'first_name' => 'A', 'phone' => '0770000000', 'joined_on' => now(),
        ]);

        return [$member, $plan];
    }

    public function test_paid_active_member_is_granted_access(): void
    {
        [$member, $plan] = $this->memberWithPlan();
        $ms = $this->billing->sellMembership($member, $plan, ['starts_on' => today()->toDateString()]);
        $this->billing->recordPayment($member, ['amount_cents' => $ms->invoice->total_cents, 'method' => 'cash']);
        $this->status->sync($member);

        $decision = $this->status->accessDecision($member);

        $this->assertTrue($decision->granted);
        $this->assertSame('active', $decision->status);
    }

    public function test_member_in_grace_period_is_warned_not_blocked(): void
    {
        [$member, $plan] = $this->memberWithPlan(graceDays: 5);
        $this->billing->sellMembership($member, $plan, ['starts_on' => today()->subDays(32)->toDateString()]);

        // ends_on was 2 days ago, grace is 5 => still inside grace.
        $decision = $this->status->accessDecision($member, today());

        $this->assertTrue($decision->granted);
        $this->assertSame('warn', $decision->level);
    }

    public function test_expired_beyond_grace_is_blocked_under_hard_block_policy(): void
    {
        config()->set('gym.access.on_expired', 'hard_block');
        [$member, $plan] = $this->memberWithPlan(graceDays: 3);
        $this->billing->sellMembership($member, $plan, ['starts_on' => today()->subDays(40)->toDateString()]);
        $this->status->sync($member);

        $decision = $this->status->accessDecision($member, today());

        $this->assertFalse($decision->granted);
        $this->assertSame('expired', $decision->status);
    }

    public function test_status_sync_marks_member_due_when_active_but_owing(): void
    {
        [$member, $plan] = $this->memberWithPlan();
        $this->billing->sellMembership($member, $plan, ['starts_on' => today()->toDateString()]);
        $this->status->sync($member);

        $this->assertSame('due', $member->refresh()->status);
        $this->assertEquals(today()->addDays(30)->toDateString(), $member->current_expiry_on->toDateString());
    }
}
