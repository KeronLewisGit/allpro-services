<?php

namespace App\Enums;

enum TicketType: string
{
    case Request = 'request';
    case Complaint = 'complaint';
    case Incident = 'incident';

    public function label(): string
    {
        return match ($this) {
            self::Request => 'Request',
            self::Complaint => 'Complaint',
            self::Incident => 'Security incident',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Request => 'Extra cleaning, a restock, a change to cover',
            self::Complaint => 'Something was missed or not up to standard',
            self::Incident => 'Something happened on site that security should know about',
        };
    }
}
