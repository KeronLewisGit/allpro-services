<?php

namespace App\Enums;

/**
 * Service levels, in clock hours from when the ticket is logged. The figures are a starting
 * point for the demo and are set per contract before launch (README, "Before launch").
 */
enum TicketPriority: string
{
    case Urgent = 'urgent';
    case High = 'high';
    case Normal = 'normal';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Urgent => 'Urgent',
            self::High => 'High',
            self::Normal => 'Normal',
            self::Low => 'Low',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Urgent => 'Safety, security or a site that cannot open',
            self::High => 'Needs doing today',
            self::Normal => 'In the next day or two',
            self::Low => 'When convenient',
        };
    }

    public function responseHours(): int
    {
        return match ($this) {
            self::Urgent => 1,
            self::High => 4,
            self::Normal => 24,
            self::Low => 48,
        };
    }

    public function resolveHours(): int
    {
        return match ($this) {
            self::Urgent => 4,
            self::High => 24,
            self::Normal => 72,
            self::Low => 120,
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Urgent => 'critical',
            self::High => 'attention',
            self::Normal => 'neutral',
            self::Low => 'muted',
        };
    }
}
