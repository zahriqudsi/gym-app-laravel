<?php

namespace App\Support\Sms;

use App\Support\Sms\Concerns\CountsSegments;
use Illuminate\Support\Facades\Http;

/**
 * notify.lk SMS gateway. Docs: https://developer.notify.lk/
 *
 * Requires NOTIFYLK_USER_ID + NOTIFYLK_API_KEY and a registered sender ID.
 * Sandbox note: notify.lk only delivers to numbers verified in your account
 * until the sender ID is approved.
 */
class NotifyLkSmsGateway implements SmsGateway
{
    use CountsSegments;

    public function send(string $to, string $body, array $options = []): SmsResult
    {
        $cfg = config('gym.sms.notifylk');

        if (empty($cfg['user_id']) || empty($cfg['api_key'])) {
            return new SmsResult(ok: false, error: 'notify.lk credentials not configured');
        }

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post(rtrim($cfg['base_url'], '/').'/send', [
                    'user_id' => $cfg['user_id'],
                    'api_key' => $cfg['api_key'],
                    'sender_id' => $options['sender'] ?? config('gym.sms.sender_id'),
                    'to' => $this->normalise($to),
                    'message' => $body,
                ]);
        } catch (\Throwable $e) {
            return new SmsResult(ok: false, error: $e->getMessage());
        }

        $json = $response->json() ?? [];
        $ok = $response->successful() && ($json['status'] ?? null) === 'success';
        $segments = $this->segments($body);

        return new SmsResult(
            ok: $ok,
            providerRef: $json['data']['message_id'] ?? $json['message_id'] ?? null,
            segments: $segments,
            costCents: $segments * (int) config('gym.sms.cost_per_segment_cents', 0),
            error: $ok ? null : ($json['message'] ?? 'notify.lk error: HTTP '.$response->status()),
        );
    }

    /** notify.lk wants msisdn in 94XXXXXXXXX form. */
    protected function normalise(string $to): string
    {
        $digits = preg_replace('/\D/', '', $to);

        if (str_starts_with($digits, '0')) {
            return '94'.substr($digits, 1);
        }
        if (str_starts_with($digits, '94')) {
            return $digits;
        }

        return '94'.$digits;
    }
}
