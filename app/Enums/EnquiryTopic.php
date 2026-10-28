<?php

namespace App\Enums;

enum EnquiryTopic: string
{
    case Services = 'services';
    case Client = 'client';
    case Careers = 'careers';
    case Supplier = 'supplier';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Services => 'Our services',
            self::Client => 'I am an existing client',
            self::Careers => 'Working for Allpro',
            self::Supplier => 'Supplying Allpro',
            self::Other => 'Something else',
        };
    }
}
