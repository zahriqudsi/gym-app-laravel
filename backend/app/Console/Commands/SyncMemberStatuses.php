<?php

namespace App\Console\Commands;

use App\Domain\Members\MembershipStatusService;
use App\Models\Member;
use Illuminate\Console\Command;

class SyncMemberStatuses extends Command
{
    protected $signature = 'gym:sync-statuses';

    protected $description = 'Recompute member status + expiry, expiring memberships past their grace period';

    public function handle(MembershipStatusService $service): int
    {
        $count = 0;

        Member::query()->whereNull('deleted_at')->chunkById(200, function ($members) use ($service, &$count) {
            foreach ($members as $member) {
                $service->sync($member);
                $count++;
            }
        });

        $this->info("Synced {$count} members.");

        return self::SUCCESS;
    }
}
