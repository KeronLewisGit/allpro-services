<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case New = 'new';
    case SurveyBooked = 'survey_booked';
    case Surveyed = 'surveyed';
    case Quoted = 'quoted';
    case Won = 'won';
    case Lost = 'lost';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::SurveyBooked => 'Survey booked',
            self::Surveyed => 'Surveyed',
            self::Quoted => 'Quote sent',
            self::Won => 'Won',
            self::Lost => 'Lost',
            self::Declined => 'Declined',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::SurveyBooked, self::Surveyed, self::Quoted => 'neutral',
            self::Won => 'good',
            self::Lost, self::Declined => 'muted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::SurveyBooked => 'calendar',
            self::Surveyed => 'clipboard',
            self::Quoted => 'mail',
            self::Won => 'check-circle',
            self::Lost, self::Declined => 'x',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::SurveyBooked, self::Surveyed, self::Quoted], true);
    }
}
