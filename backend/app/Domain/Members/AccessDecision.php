<?php

namespace App\Domain\Members;

final class AccessDecision
{
    public function __construct(
        public readonly bool $granted,
        public readonly string $status,   // active, due, expired, frozen, cancelled, none
        public readonly string $level,    // ok, warn, block
        public readonly string $message,
    ) {}

    public static function ok(string $status = 'active'): self
    {
        return new self(true, $status, 'ok', 'Access granted.');
    }

    public static function warn(string $status, string $message): self
    {
        return new self(true, $status, 'warn', $message);
    }

    public static function block(string $status, string $message): self
    {
        return new self(false, $status, 'block', $message);
    }
}
