<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Support\Sms\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One-off / broadcast SMS that is not tied to a reminder rule.
 */
class SendAdhocSms implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public ?int $memberId,
        public string $to,
        public string $body,
        public string $purpose = 'campaign',
    ) {}

    public function handle(SmsGateway $sms): void
    {
        $result = $sms->send($this->to, $this->body, ['member_id' => $this->memberId]);

        SmsMessage::create([
            'member_id' => $this->memberId,
            'to' => $this->to,
            'body' => $this->body,
            'segments' => $result->segments,
            'cost_cents' => $result->costCents,
            'provider' => config('gym.sms.driver'),
            'provider_ref' => $result->providerRef,
            'purpose' => $this->purpose,
            'status' => $result->ok ? 'sent' : 'failed',
            'error' => $result->error,
            'sent_at' => $result->ok ? now() : null,
        ]);

        if (! $result->ok) {
            $this->release(120);
        }
    }
}
