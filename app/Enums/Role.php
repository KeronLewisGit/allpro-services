<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Sales = 'sales';
    case Hr = 'hr';
    case Operations = 'operations';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Sales => 'Sales',
            self::Hr => 'Human resources',
            self::Operations => 'Operations',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Everything, including staff accounts.',
            self::Sales => 'Quote and site-survey requests for their region, and contact messages.',
            self::Hr => 'Job postings, applications, and the staff licence and vetting register.',
            self::Operations => 'The client service desk: client sites, their private links and requests.',
        };
    }

    /**
     * Back-office areas this role works in.
     *
     * @return list<Area>
     */
    public function areas(): array
    {
        return match ($this) {
            self::Admin => Area::cases(),
            self::Sales => [Area::Quotes, Area::Enquiries],
            self::Hr => [Area::Careers, Area::Staff],
            self::Operations => [Area::ServiceDesk],
        };
    }
}
