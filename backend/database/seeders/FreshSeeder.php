<?php

namespace Database\Seeders;

use App\Domain\Notifications\DefaultTemplates;
use App\Models\Branch;
use App\Models\MessageTemplate;
use App\Models\ReminderRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A clean start for a real gym: the operational scaffolding every gym needs
 * (one branch, one owner login, the standard reminder rules and editable SMS
 * templates) and NO customer data — no members, plans, invoices or payments.
 *
 *   php artisan migrate:fresh --seeder="Database\Seeders\FreshSeeder"
 */
class FreshSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        $branch = Branch::firstOrCreate(['code' => 'MAIN'], [
            'name' => 'Main Branch',
            'is_active' => true,
        ]);

        $owner = User::firstOrCreate(['email' => 'owner@gym.lk'], [
            'name' => 'Owner',
            'password' => Hash::make('password'),
            'staff_role' => 'owner',
            'is_active' => true,
            'default_branch_id' => $branch->id,
        ]);
        $owner->syncRoles(['owner']);

        // Editable copies of the built-in reminder wording.
        foreach (DefaultTemplates::SMS as $key => $body) {
            MessageTemplate::firstOrCreate(
                ['key' => $key, 'channel' => 'sms', 'language' => 'en'],
                ['name' => ucfirst(str_replace('_', ' ', $key)), 'body' => $body, 'is_active' => true],
            );
        }

        // Standard reminder schedule — tweak or disable from Messaging → Reminder rules.
        $rules = [
            ['name' => '7 days before expiry', 'event' => 'before_expiry', 'offset_days' => -7],
            ['name' => '3 days before expiry', 'event' => 'before_expiry', 'offset_days' => -3],
            ['name' => 'On expiry day',        'event' => 'on_expiry',     'offset_days' => 0],
            ['name' => '3 days after expiry',  'event' => 'after_expiry',  'offset_days' => 3],
            ['name' => 'Payment due',          'event' => 'payment_due',   'offset_days' => 0],
            ['name' => 'Welcome',              'event' => 'welcome',       'offset_days' => 0],
            ['name' => 'Birthday',             'event' => 'birthday',      'offset_days' => 0],
        ];
        foreach ($rules as $r) {
            ReminderRule::firstOrCreate(['name' => $r['name']], $r + [
                'channel' => 'sms', 'template_key' => $r['event'], 'language' => 'en', 'is_active' => true,
            ]);
        }

        $this->command?->info('Fresh install ready. Sign in at /admin with owner@gym.lk / password — then change the password.');
    }
}
