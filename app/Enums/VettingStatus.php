<?php

namespace App\Enums;

/**
 * Garda vetting through the National Vetting Bureau's e-vetting system. HR records the stage;
 * no vetting form or disclosure is ever uploaded through the website.
 */
enum VettingStatus: string
{
    case NotStarted = 'not_started';
    case Invited = 'invited';
    case Submitted = 'submitted';
    case Cleared = 'cleared';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::Invited => 'E-vetting invitation sent',
            self::Submitted => 'Submitted to the NVB',
            self::Cleared => 'Disclosure received',
            self::Refused => 'Not suitable',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::NotStarted => 'muted',
            self::Invited, self::Submitted => 'attention',
            self::Cleared => 'good',
            self::Refused => 'critical',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cleared => 'check-circle',
            self::Refused => 'x',
            self::NotStarted => 'dot',
            default => 'clock',
        };
    }
}
