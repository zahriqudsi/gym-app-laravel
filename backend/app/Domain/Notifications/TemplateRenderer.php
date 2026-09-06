<?php

namespace App\Domain\Notifications;

use App\Domain\Support\Money;
use App\Models\Member;

class TemplateRenderer
{
    /** Replace {placeholders} in a template body with values. */
    public function render(string $body, array $vars): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function ($m) use ($vars) {
            return array_key_exists($m[1], $vars) ? (string) $vars[$m[1]] : $m[0];
        }, $body);
    }

    /** Standard merge variables available for a member-targeted message. */
    public function memberVars(Member $member, array $extra = []): array
    {
        return array_merge([
            'name' => $member->first_name ?: $member->name,
            'full_name' => $member->name,
            'member_no' => $member->member_no,
            'gym' => config('gym.name'),
            'branch' => $member->branch?->name ?? config('gym.name'),
            'expiry_date' => optional($member->current_expiry_on)->toDateString() ?? '',
            'phone' => $member->phone ?? '',
        ], $extra);
    }

    public function duesVar(int $cents): string
    {
        return Money::format($cents, withSymbol: false);
    }
}
