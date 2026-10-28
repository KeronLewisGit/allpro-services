<?php

namespace App\Enums;

/** The three kinds of front-line role Allpro recruits for. */
enum RoleType: string
{
    case SecurityOfficer = 'security_officer';
    case CleaningOperative = 'cleaning_operative';
    case Supervisor = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::SecurityOfficer => 'Security officer',
            self::CleaningOperative => 'Cleaning operative',
            self::Supervisor => 'Supervisor',
        };
    }

    public function plural(): string
    {
        return match ($this) {
            self::SecurityOfficer => 'Security officers',
            self::CleaningOperative => 'Cleaning operatives',
            self::Supervisor => 'Supervisors',
        };
    }

    /** Security officers must give a current PSA licence number and expiry. */
    public function needsPsa(): bool
    {
        return $this === self::SecurityOfficer;
    }

    /** Supervisors may hold a PSA licence (security supervisors) but need not. */
    public function asksPsa(): bool
    {
        return $this !== self::CleaningOperative;
    }

    public function icon(): string
    {
        return match ($this) {
            self::SecurityOfficer => 'shield',
            self::CleaningOperative => 'sparkle',
            self::Supervisor => 'clipboard',
        };
    }
}
