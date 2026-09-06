<?php

namespace App\Filament\Pages;

use App\Domain\Members\AccessDecision;
use App\Domain\Members\MembershipStatusService;
use App\Models\Attendance;
use App\Models\Member;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class CheckIn extends Page
{
    protected string $view = 'filament.pages.check-in';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'Check-in';

    public string $q = '';

    public ?int $memberId = null;

    public function updatedQ(): void
    {
        $this->memberId = $this->resolveMember()?->id;
    }

    protected function resolveMember(): ?Member
    {
        $term = trim($this->q);
        if (mb_strlen($term) < 2) {
            return null;
        }

        return Member::query()
            ->where('member_no', $term)
            ->orWhere('phone', $term)
            ->orWhere('phone', 'like', '%'.$term)
            ->orWhere(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%");
            })
            ->orderByRaw('member_no = ? desc', [$term])
            ->first();
    }

    public function getMemberProperty(): ?Member
    {
        return $this->memberId ? Member::with('branch')->find($this->memberId) : null;
    }

    public function getDecisionProperty(): ?AccessDecision
    {
        $member = $this->member;

        return $member
            ? app(MembershipStatusService::class)->accessDecision($member)
            : null;
    }

    /** An open check-in for this member inside the duplicate window. */
    public function getOpenAttendanceProperty(): ?Attendance
    {
        $member = $this->member;
        if (! $member) {
            return null;
        }

        $window = (int) config('gym.access.duplicate_checkin_minutes', 60);

        return $member->attendances()
            ->whereNull('checked_out_at')
            ->where('checked_in_at', '>=', now()->subMinutes($window))
            ->latest('checked_in_at')
            ->first();
    }

    public function checkIn(): void
    {
        $member = $this->member;
        $decision = $this->decision;

        if (! $member || ! $decision) {
            return;
        }

        if ($this->openAttendance) {
            Notification::make()->warning()->title($member->name.' is already checked in.')->send();

            return;
        }

        if (! $decision->granted) {
            Notification::make()->danger()
                ->title('Access denied — '.$member->name)
                ->body($decision->message)
                ->persistent()
                ->send();
            $this->reset('q', 'memberId');

            return;
        }

        Attendance::create([
            'branch_id' => $member->branch_id,
            'member_id' => $member->id,
            'checked_in_at' => now(),
            'method' => 'manual',
            'status_at_checkin' => $decision->status,
            'access_granted' => true,
        ]);

        Notification::make()
            ->title($member->name.' checked in'.($decision->level === 'warn' ? ' (with warning)' : ''))
            ->color($decision->level === 'warn' ? 'warning' : 'success')
            ->body($decision->level === 'warn' ? $decision->message : null)
            ->send();

        $this->reset('q', 'memberId');
    }

    public function checkOut(int $attendanceId): void
    {
        Attendance::whereKey($attendanceId)->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        Notification::make()->title('Checked out')->send();
    }

    /** @return Collection<int, Attendance> */
    public function getInsideProperty(): Collection
    {
        return Attendance::with('member')
            ->whereNull('checked_out_at')
            ->whereDate('checked_in_at', today())
            ->latest('checked_in_at')
            ->get();
    }

    /** @return Collection<int, Attendance> */
    public function getRecentProperty(): Collection
    {
        return Attendance::with('member')
            ->whereDate('checked_in_at', today())
            ->latest('checked_in_at')
            ->limit(15)
            ->get();
    }
}
