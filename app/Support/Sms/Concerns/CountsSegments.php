<?php

namespace App\Support\Sms\Concerns;

trait CountsSegments
{
    public function segments(string $body): int
    {
        $len = mb_strlen($body);

        // Non-GSM characters (e.g. Sinhala/Tamil) force UCS-2 encoding.
        $isUnicode = (bool) preg_match('/[^\x00-\x7F]/u', $body)
            && ! preg_match('/^[\x00-\x7F€£¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞ]*$/u', $body);

        if ($isUnicode) {
            return $len <= 70 ? 1 : (int) ceil($len / 67);
        }

        return $len <= 160 ? 1 : (int) ceil($len / 153);
    }
}
