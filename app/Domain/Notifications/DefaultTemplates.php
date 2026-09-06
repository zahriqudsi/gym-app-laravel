<?php

namespace App\Domain\Notifications;

/**
 * Fallback SMS copy used when no MessageTemplate row matches a reminder rule.
 * Seed editable versions of these into the message_templates table.
 */
final class DefaultTemplates
{
    public const SMS = [
        'welcome' => 'Hi {name}, welcome to {gym}! Your membership is active until {expiry_date}.',
        'before_expiry' => 'Hi {name}, your {gym} membership expires on {expiry_date}. Please renew to keep your access.',
        'on_expiry' => 'Hi {name}, your {gym} membership expires today. Renew today to avoid a break.',
        'after_expiry' => 'Hi {name}, your {gym} membership expired on {expiry_date}. Come in to renew - we would love to see you back.',
        'payment_due' => 'Hi {name}, you have an outstanding balance of {amount_due} at {gym}. Please settle it at the front desk.',
        'birthday' => 'Happy birthday {name}! Enjoy a guest pass on us this month - show this message at {gym} reception.',
        'winback' => 'We miss you at {gym}, {name}! Drop by this week for a free session. Reply YES to book.',
    ];

    public static function for(string $event, string $channel = 'sms'): string
    {
        return self::SMS[$event] ?? 'Hi {name}, a message from {gym}.';
    }
}
