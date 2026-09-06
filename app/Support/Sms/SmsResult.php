<?php

namespace App\Support\Sms;

final class SmsResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $providerRef = null,
        public readonly int $segments = 1,
        public readonly int $costCents = 0,
        public readonly ?string $error = null,
    ) {}
}
