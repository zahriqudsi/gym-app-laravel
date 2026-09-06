<?php

namespace App\Domain\Members;

use App\Domain\Billing\BillingService;
use App\Models\Member;
use App\Models\Membership;
use Illuminate\Support\Carbon;

class MembershipStatusService
{
    public function __construct(protected BillingService $billing) {}

    /**
     * Recompute a member's denormalised status + expiry from their memberships.
     * Call this after selling/renewing/freezing a membership, and nightly.
     */
    public function sync(Member $member, ?Carbon $on = null): void
    {
        $on ??= today();

        // Expire duration memberships that ran past ends_on + plan grace.
        $member->memberships()
            ->where('status', 'active')
            ->whereNotNull('ends_on')
            ->with('plan')
            ->get()
            ->each(function (Membership $m) use ($on) {
                $grace = (int) ($m->plan->grace_days ?? 0);
                if ($m->ends_on->copy()->addDays($grace)->lt($on)) {
                    $m->update(['status' => 'expired']);
                }
            });

        $active = $member->memberships()
            ->where('status', 'active')
            ->orderByDesc('ends_on')
            ->first();

        $hasDues = $this->billing->memberDuesCents($member) > 0;

        if (! $active) {
            $status = $member->memberships()->where('status', 'frozen')->exists() ? 'frozen' : 'expired';
            if ($member->memberships()->count() === 0) {
                $status = $member->status === 'enquiry' ? 'enquiry' : 'cancelled';
            }
        } elseif ($hasDues) {
            $status = 'due';
        } else {
            $status = 'active';
        }

        $member->update([
            'status' => $status,
            'current_expiry_on' => $active?->ends_on,
        ]);
    }

    /** Decide whether a member may check in right now. */
    public function accessDecision(Member $member, ?Carbon $on = null): AccessDecision
    {
        $on ??= today();

        if ($member->memberships()->where('status', 'frozen')->exists()) {
            return $this->applyPolicy('on_frozen', 'frozen', 'Membership is frozen.');
        }

        $active = $member->memberships()
            ->where('status', 'active')
            ->orderByDesc('ends_on')
            ->with('plan')
            ->first();

        if (! $active) {
            return AccessDecision::block('expired', 'No active membership. Please renew at the front desk.');
        }

        // Session pack with no sessions left.
        if ($active->sessions_total !== null && $active->sessionsRemaining() <= 0) {
            return AccessDecision::block('expired', 'No sessions remaining on the current package.');
        }

        // Past expiry but inside grace period.
        if ($active->ends_on) {
            $grace = (int) ($active->plan->grace_days ?? 0);
            $hardExpiry = $active->ends_on->copy()->addDays($grace);

            if ($on->gt($hardExpiry)) {
                return $this->applyPolicy('on_expired', 'expired', 'Membership expired on '.$active->ends_on->toDateString().'.');
            }

            if ($on->gt($active->ends_on)) {
                return AccessDecision::warn('due', 'In grace period — expired on '.$active->ends_on->toDateString().'.');
            }
        }

        if ($this->billing->memberDuesCents($member) > 0) {
            return $this->applyPolicy('on_dues', 'due', 'Outstanding balance on the account.');
        }

        return AccessDecision::ok('active');
    }

    protected function applyPolicy(string $configKey, string $status, string $message): AccessDecision
    {
        $policy = config("gym.access.$configKey", 'hard_block');

        return $policy === 'allow_warn'
            ? AccessDecision::warn($status, $message)
            : AccessDecision::block($status, $message);
    }
}
