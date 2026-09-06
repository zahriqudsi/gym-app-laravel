<?php

namespace App\Support\Sms;

interface SmsGateway
{
    /**
     * Send one SMS. Implementations must not throw for provider-side failures —
     * return SmsResult with ok=false and an error string instead.
     */
    public function send(string $to, string $body, array $options = []): SmsResult;

    /** Estimate GSM/Unicode segment count for cost + preview. */
    public function segments(string $body): int;
}
