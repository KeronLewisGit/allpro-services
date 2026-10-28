<?php

namespace App\Enums;

enum TicketCategory: string
{
    case Cleaning = 'cleaning';
    case Security = 'security';
    case Washroom = 'washroom';
    case Consumables = 'consumables';
    case Grounds = 'grounds';
    case Pest = 'pest';
    case Staff = 'staff';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cleaning => 'Cleaning',
            self::Security => 'Security',
            self::Washroom => 'Washrooms and sanitary',
            self::Consumables => 'Consumables and restocking',
            self::Grounds => 'Grounds and landscaping',
            self::Pest => 'Pest control',
            self::Staff => 'Staff conduct or attendance',
            self::Other => 'Something else',
        };
    }
}
