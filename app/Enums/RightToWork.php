<?php

namespace App\Enums;

/** What the applicant declares. HR checks the documents in person before anyone starts. */
enum RightToWork: string
{
    case Citizen = 'citizen';
    case Permission = 'permission';
    case NeedsPermit = 'needs_permit';

    public function label(): string
    {
        return match ($this) {
            self::Citizen => 'Irish, UK, EU, EEA or Swiss citizen',
            self::Permission => 'I have immigration permission that lets me work (for example Stamp 4)',
            self::NeedsPermit => 'I would need an employment permit',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Citizen => 'Citizen (IE/UK/EU/EEA/CH)',
            self::Permission => 'Permission to work',
            self::NeedsPermit => 'Needs a permit',
        };
    }
}
