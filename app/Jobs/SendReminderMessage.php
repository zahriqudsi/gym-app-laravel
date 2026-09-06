<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\SmsMessage;
use App\Support\Sms\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendReminderMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $notificationLogId,
        public int $memberId,
        public string $to,
        public string $body,
        public string $channel = 'sms',
    ) {}

    public function handle(SmsGateway $sms): void
    {
        $log = NotificationLog::find($this->notificationLogId);

        if (! $log || $log->status === 'sent') {
            return;
        }

        if ($this->channel !== 'sms') {
            // WhatsApp / email channels are added later; mark as skipped for now.
            $log->update(['status' => 'failed', 'error' => "channel '{$this->channel}' not implemented"]);

            return;
        }

        $result = $sms->send($this->to, $this->body, ['member_id' => $this->memberId]);

        $log->update([
            'status' => $result->ok ? 'sent' : 'failed',
            'provider_ref' => $result->providerRef,
            'cost_cents' => $result->costCents,
            'error' => $result->error,
            'sent_at' => $result->ok ? now() : null,
        ]);

        SmsMessage::create([
            'member_id' => $this->memberId,
            'to' => $this->to,
            'body' => $this->body,
            'segments' => $result->segments,
            'cost_cents' => $result->costCents,
            'provider' => config('gym.sms.driver'),
            'provider_ref' => $result->providerRef,
            'purpose' => 'reminder',
            'status' => $result->ok ? 'sent' : 'failed',
            'error' => $result->error,
            'sent_at' => $result->ok ? now() : null,
        ]);

        if (! $result->ok) {
            $this->release(120);
        }
    }
}
