<?php

namespace App\Enums;

/** A part of the back office. Role::areas() says who works where; policies and the nav read it. */
enum Area: string
{
    case Careers = 'careers';
    case Staff = 'staff';
    case Quotes = 'quotes';
    case ServiceDesk = 'servicedesk';
    case Enquiries = 'enquiries';

    public function label(): string
    {
        return match ($this) {
            self::Careers => 'Careers',
            self::Staff => 'Staff licences',
            self::Quotes => 'Quotes and surveys',
            self::ServiceDesk => 'Client service desk',
            self::Enquiries => 'Enquiries',
        };
    }

    /** Which ALLPRO_NOTIFY_* inbox hears about new submissions in this area. */
    public function inbox(): string
    {
        return $this->value;
    }
}
