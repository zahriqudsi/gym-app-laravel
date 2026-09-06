<?php

namespace App\Support\Sms;

use App\Support\Sms\Concerns\CountsSegments;
use Illuminate\Support\Facades\Log;

/**
 * Default driver for local development. Writes the message to the log instead
 * of sending it, and always reports success.
 */
class LogSmsGateway implements SmsGateway
{
    use CountsSegments;

    public function send(string $to, string $body, array $options = []): SmsResult
    {
        $segments = $this->segments($body);

        Log::channel(config('gym.sms.log_channel', 'stack'))->info('[SMS:log] outbound', [
            'to' => $to,
            'sender' => $options['sender'] ?? config('gym.sms.sender_id'),
            'segments' => $segments,
            'body' => $body,
        ]);

        return new SmsResult(
            ok: true,
            providerRef: 'log-'.uniqid(),
            segments: $segments,
            costCents: $segments * (int) config('gym.sms.cost_per_segment_cents', 0),
        );
    }
}
