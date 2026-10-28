<?php

namespace App\Enums;

/** The hiring pipeline. Vetting is a stage, tracked by HR, never collected on the public form. */
enum ApplicationStatus: string
{
    case New = 'new';
    case Screening = 'screening';
    case Interview = 'interview';
    case Vetting = 'vetting';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Screening => 'Phone screen',
            self::Interview => 'Interview',
            self::Vetting => 'Vetting and checks',
            self::Offer => 'Offer made',
            self::Hired => 'Hired',
            self::Rejected => 'Not progressed',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Screening, self::Interview, self::Vetting => 'neutral',
            self::Offer, self::Hired => 'good',
            self::Rejected, self::Withdrawn => 'muted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Screening => 'phone',
            self::Interview => 'users',
            self::Vetting => 'shield',
            self::Offer => 'mail',
            self::Hired => 'check-circle',
            self::Rejected, self::Withdrawn => 'x',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Hired, self::Rejected, self::Withdrawn], true);
    }

    /** @return list<self> the stages shown as pipeline columns */
    public static function pipeline(): array
    {
        return [self::New, self::Screening, self::Interview, self::Vetting, self::Offer, self::Hired];
    }
}
