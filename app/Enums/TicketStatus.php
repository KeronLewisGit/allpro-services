<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Acknowledged = 'acknowledged';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Logged',
            self::Acknowledged => 'Acknowledged',
            self::InProgress => 'In progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Acknowledged, self::InProgress => 'neutral',
            self::Resolved => 'good',
            self::Closed => 'muted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Acknowledged => 'check',
            self::InProgress => 'clock',
            self::Resolved => 'check-circle',
            self::Closed => 'x',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Acknowledged, self::InProgress], true);
    }
}
