<?php

namespace Database\Seeders;

use App\Domain\Billing\BillingService;
use App\Domain\Members\MembershipStatusService;
use App\Domain\Notifications\DefaultTemplates;
use App\Domain\Support\DocumentNumber;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Member;
use App\Models\MessageTemplate;
use App\Models\Plan;
use App\Models\ReminderRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $billing = app(BillingService::class);
        $status = app(MembershipStatusService::class);

        $branch = Branch::firstOrCreate(['code' => 'MAIN'], [
            'name' => 'Main Branch',
            'phone' => '011 234 5678',
            'address' => 'Colombo',
            'opening_time' => '05:00',
            'closing_time' => '22:00',
            'sms_sender_id' => config('gym.sms.sender_id'),
        ]);

        $owner = User::firstOrCreate(['email' => 'owner@demo.test'], [
            'name' => 'Gym Owner',
            'password' => Hash::make('password'),
            'phone' => '0771234567',
            'staff_role' => 'owner',
            'is_active' => true,
            'default_branch_id' => $branch->id,
        ]);
        $owner->assignRole('owner');

        $reception = User::firstOrCreate(['email' => 'reception@demo.test'], [
            'name' => 'Front Desk',
            'password' => Hash::make('password'),
            'staff_role' => 'receptionist',
            'is_active' => true,
            'default_branch_id' => $branch->id,
        ]);
        $reception->assignRole('receptionist');

        // ---- Plans -------------------------------------------------------------
        $plans = collect([
            ['name' => 'Monthly',        'category' => 'gym',     'billing_type' => 'duration', 'duration_days' => 30,  'price_cents' => 500000,  'signup_fee_cents' => 100000, 'grace_days' => 3],
            ['name' => 'Quarterly',      'category' => 'gym',     'billing_type' => 'duration', 'duration_days' => 90,  'price_cents' => 1350000, 'signup_fee_cents' => 100000, 'grace_days' => 3],
            ['name' => 'Annual',         'category' => 'gym',     'billing_type' => 'duration', 'duration_days' => 365, 'price_cents' => 4500000, 'signup_fee_cents' => 0,      'grace_days' => 7],
            ['name' => 'Student Monthly','category' => 'student', 'billing_type' => 'duration', 'duration_days' => 30,  'price_cents' => 350000,  'signup_fee_cents' => 0,      'grace_days' => 3],
            ['name' => 'PT 12 Sessions', 'category' => 'pt',      'billing_type' => 'sessions', 'session_count' => 12,  'price_cents' => 2400000, 'signup_fee_cents' => 0,      'grace_days' => 0],
        ])->map(fn ($p) => Plan::firstOrCreate(['name' => $p['name']], $p));

        // ---- Message templates (editable copies of the built-in defaults) -----
        foreach (DefaultTemplates::SMS as $key => $body) {
            MessageTemplate::firstOrCreate(
                ['key' => $key, 'channel' => 'sms', 'language' => 'en'],
                ['name' => ucfirst(str_replace('_', ' ', $key)), 'body' => $body, 'is_active' => true],
            );
        }

        // ---- Reminder rules --------------------------------------------------
        $rules = [
            ['name' => '7 days before expiry',  'event' => 'before_expiry', 'offset_days' => -7],
            ['name' => '3 days before expiry',  'event' => 'before_expiry', 'offset_days' => -3],
            ['name' => 'On expiry day',         'event' => 'on_expiry',     'offset_days' => 0],
            ['name' => '3 days after expiry',   'event' => 'after_expiry',  'offset_days' => 3],
            ['name' => 'Payment due',           'event' => 'payment_due',   'offset_days' => 0],
            ['name' => 'Welcome',               'event' => 'welcome',       'offset_days' => 0],
            ['name' => 'Birthday',              'event' => 'birthday',      'offset_days' => 0],
        ];
        foreach ($rules as $r) {
            ReminderRule::firstOrCreate(['name' => $r['name']], $r + [
                'channel' => 'sms', 'template_key' => $r['event'], 'language' => 'en', 'is_active' => true,
            ]);
        }

        // ---- Members ------------------------------------------------------------
        if (Member::count() === 0) {
            for ($i = 0; $i < 30; $i++) {
                $joined = now()->subDays(fake()->numberBetween(5, 400));

                $member = Member::create([
                    'branch_id' => $branch->id,
                    'member_no' => DocumentNumber::member(),
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'nic' => fake()->numerify('20########').fake()->randomElement(['V', '']),
                    'phone' => '07'.fake()->numberBetween(0, 9).fake()->numerify('#######'),
                    'email' => fake()->optional()->safeEmail(),
                    'dob' => fake()->dateTimeBetween('-55 years', '-16 years'),
                    'gender' => fake()->randomElement(['male', 'female']),
                    'joined_on' => $joined,
                    'referral_source' => fake()->randomElement(['walk-in', 'friend', 'facebook', 'instagram', null]),
                ]);

                // Sell 1-3 successive memberships starting near the join date.
                $plan = $plans->random();
                $terms = fake()->numberBetween(1, 3);
                $startsOn = $joined->copy();

                for ($t = 0; $t < $terms; $t++) {
                    $membership = $billing->sellMembership($member, $plan, ['starts_on' => $startsOn]);
                    $startsOn = ($membership->ends_on ?? $startsOn)->copy()->addDay();

                    // Payment behaviour: 70% full, 15% partial, 15% unpaid.
                    $invoice = $membership->invoice;
                    $roll = fake()->numberBetween(1, 100);
                    if ($roll <= 70) {
                        $billing->recordPayment($member, [
                            'amount_cents' => $invoice->total_cents,
                            'method' => fake()->randomElement(['cash', 'card', 'lankaqr']),
                            'paid_at' => $membership->starts_on,
                        ]);
                    } elseif ($roll <= 85) {
                        $billing->recordPayment($member, [
                            'amount_cents' => (int) round($invoice->total_cents * 0.5),
                            'method' => 'cash',
                            'paid_at' => $membership->starts_on,
                        ]);
                    }
                }

                $status->sync($member);

                // Some recent attendance for active-ish members.
                if (in_array($member->status, ['active', 'due'])) {
                    foreach (range(1, fake()->numberBetween(2, 12)) as $d) {
                        Attendance::create([
                            'branch_id' => $branch->id,
                            'member_id' => $member->id,
                            'checked_in_at' => now()->subDays(fake()->numberBetween(0, 20))->setTime(fake()->numberBetween(6, 20), 0),
                            'method' => fake()->randomElement(['qr', 'manual', 'nic']),
                            'status_at_checkin' => $member->status,
                        ]);
                    }
                }
            }
        }

        // ---- Leads ------------------------------------------------------------
        if (Lead::count() === 0) {
            foreach (range(1, 8) as $i) {
                Lead::create([
                    'branch_id' => $branch->id,
                    'name' => fake()->name(),
                    'phone' => '07'.fake()->numerify('########'),
                    'source' => fake()->randomElement(['walk_in', 'call', 'web', 'social', 'referral']),
                    'interest' => fake()->randomElement(['Weight loss', 'Muscle gain', 'General fitness', 'Personal training']),
                    'stage' => fake()->randomElement(['new', 'contacted', 'trial_booked', 'negotiation']),
                    'owner_id' => $reception->id,
                    'next_follow_up_on' => now()->addDays(fake()->numberBetween(-3, 7)),
                ]);
            }
        }
    }
}
