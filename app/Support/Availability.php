<?php

namespace App\Support;

/**
 * The availability grid on the job application: seven days by three shifts. Stored as a list
 * of "mon-early" keys, so HR can filter for "can do nights" or "weekends".
 */
final class Availability
{
    public const array DAYS = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];

    public const array SHIFTS = [
        'early' => ['Early', '6 am – 2 pm'],
        'late' => ['Late', '2 pm – 10 pm'],
        'night' => ['Night', '10 pm – 6 am'],
    ];

    /** @return list<string> every valid "day-shift" key */
    public static function keys(): array
    {
        $keys = [];
        foreach (array_keys(self::DAYS) as $d) {
            foreach (array_keys(self::SHIFTS) as $s) {
                $keys[] = $d.'-'.$s;
            }
        }

        return $keys;
    }

    /**
     * Keep only valid keys, in grid order.
     *
     * @param  array<mixed>  $input
     * @return list<string>
     */
    public static function clean(array $input): array
    {
        $given = array_filter($input, is_string(...));

        return array_values(array_intersect(self::keys(), $given));
    }

    /**
     * "Weekdays early and late; weekend nights": a short summary for lists and exports.
     *
     * @param  list<string>|null  $slots
     */
    public static function summary(?array $slots): string
    {
        $slots = $slots ?? [];
        if ($slots === []) {
            return 'Not given';
        }
        if (count($slots) === count(self::keys())) {
            return 'Any shift, any day';
        }
        $parts = [];
        foreach (self::SHIFTS as $s => [$label]) {
            $days = array_values(array_filter(array_keys(self::DAYS), fn (string $d) => in_array($d.'-'.$s, $slots, true)));
            if ($days === []) {
                continue;
            }
            $parts[] = $label.' '.self::dayRange($days);
        }

        return implode('; ', $parts);
    }

    /** @param list<string> $slots */
    public static function covers(array $slots, string $shift): bool
    {
        return (bool) array_filter($slots, fn (string $k) => str_ends_with($k, '-'.$shift));
    }

    /** @param list<string> $days */
    private static function dayRange(array $days): string
    {
        $weekdays = ['mon', 'tue', 'wed', 'thu', 'fri'];
        if ($days === array_keys(self::DAYS)) {
            return 'every day';
        }
        if ($days === $weekdays) {
            return 'weekdays';
        }
        if ($days === ['sat', 'sun']) {
            return 'weekends';
        }

        return implode(', ', array_map(fn (string $d) => self::DAYS[$d], $days));
    }
}
