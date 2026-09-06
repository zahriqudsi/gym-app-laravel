<?php

namespace App\Domain\Notifications;

use App\Domain\Billing\BillingService;
use App\Jobs\SendReminderMessage;
use App\Models\Member;
use App\Models\MessageTemplate;
use App\Models\NotificationLog;
use App\Models\ReminderRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReminderRunner
{
    public function __construct(
        protected TemplateRenderer $renderer,
        protected BillingService $billing,
    ) {}

    /**
     * Evaluate every active reminder rule for the given date and queue messages.
     *
     * @return array{rules:int, queued:int, skipped:int}
     */
    public function run(?Carbon $on = null): array
    {
        $on ??= today();
        $rules = ReminderRule::where('is_active', true)->get();

        $queued = 0;
        $skipped = 0;

        foreach ($rules as $rule) {
            if ($rule->respect_quiet_hours && $this->inQuietHours()) {
                continue;
            }

            foreach ($this->membersFor($rule, $on) as $member) {
                $result = $this->queueForMember($rule, $member, $on);
                $result ? $queued++ : $skipped++;
            }
        }

        return ['rules' => $rules->count(), 'queued' => $queued, 'skipped' => $skipped];
    }

    /** @return \Illuminate\Support\LazyCollection<int, Member> */
    protected function membersFor(ReminderRule $rule, Carbon $on)
    {
        $q = Member::query()->whereNull('deleted_at');

        return match ($rule->event) {
            'before_expiry', 'on_expiry', 'after_expiry' => $q
                ->whereDate('current_expiry_on', $on->copy()->subDays($rule->offset_days))
                ->cursor(),

            'payment_due' => $q->where('status', 'due')->cursor(),

            'welcome' => $q->whereDate('joined_on', $on->copy()->subDays(max(0, $rule->offset_days)))->cursor(),

            'birthday' => $q->whereNotNull('dob')
                ->whereMonth('dob', $on->month)
                ->whereDay('dob', $on->day)
                ->cursor(),

            'winback' => $q->whereIn('status', ['expired', 'cancelled'])
                ->whereDoesntHave('attendances', fn ($a) => $a->where('checked_in_at', '>=', $on->copy()->subDays(abs($rule->offset_days) ?: 30)))
                ->cursor(),

            default => collect()->lazy(),
        };
    }

    protected function queueForMember(ReminderRule $rule, Member $member, Carbon $on): bool
    {
        if ($rule->channel === 'sms' && blank($member->phone)) {
            return false;
        }

        $dedupeKey = "{$rule->event}:{$rule->offset_days}:rule{$rule->id}:member{$member->id}:{$on->toDateString()}";

        // Atomic claim: unique index on dedupe_key stops duplicates across workers.
        try {
            $log = NotificationLog::create([
                'member_id' => $member->id,
                'reminder_rule_id' => $rule->id,
                'channel' => $rule->channel,
                'template_key' => $rule->template_key ?: $rule->event,
                'dedupe_key' => $dedupeKey,
                'status' => 'queued',
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return false;
        }

        $body = $this->resolveBody($rule, $member);

        SendReminderMessage::dispatch($log->id, $member->id, (string) $member->phone, $body, $rule->channel);

        return true;
    }

    protected function resolveBody(ReminderRule $rule, Member $member): string
    {
        $key = $rule->template_key ?: $rule->event;

        $template = MessageTemplate::query()
            ->where('key', $key)
            ->where('channel', $rule->channel)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('language', $rule->language)->orWhere('language', 'en'))
            ->orderByRaw('CASE WHEN language = ? THEN 0 ELSE 1 END', [$rule->language])
            ->first();

        $vars = $this->renderer->memberVars($member, [
            'amount_due' => $this->renderer->duesVar($this->billing->memberDuesCents($member)),
        ]);

        $body = $template?->body ?? DefaultTemplates::for($rule->event, $rule->channel);

        return $this->renderer->render($body, $vars);
    }

    protected function inQuietHours(?Carbon $now = null): bool
    {
        $now ??= now();
        $start = config('gym.reminders.quiet_hours.start', '21:00');
        $end = config('gym.reminders.quiet_hours.end', '08:00');
        $t = $now->format('H:i');

        // Window wraps midnight (e.g. 21:00 -> 08:00).
        return $start > $end
            ? ($t >= $start || $t < $end)
            : ($t >= $start && $t < $end);
    }
}
