<?php

namespace App\Enums;

enum Experience: string
{
    case None = 'none';
    case UnderOne = 'under_1';
    case OneToThree = '1_3';
    case ThreePlus = '3_plus';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None yet, happy to train',
            self::UnderOne => 'Less than a year',
            self::OneToThree => '1 to 3 years',
            self::ThreePlus => 'More than 3 years',
        };
    }
}
