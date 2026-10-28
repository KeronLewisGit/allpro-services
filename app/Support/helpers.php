<?php

if (! function_exists('allpro')) {
    /** A fact from config/allpro.php: allpro('phone'), allpro('services'). */
    function allpro(string $key, mixed $default = null): mixed
    {
        return config('allpro.'.$key, $default);
    }
}

if (! function_exists('ie_digits')) {
    /**
     * E.164 digits (353…) for an Irish number written any common way: 091 381111,
     * +353 91 381 111, 00353 87 123 4567. Null when it is not an Irish number.
     */
    function ie_digits(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        if (str_starts_with($digits, '00353')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0') && strlen($digits) >= 9 && strlen($digits) <= 10) {
            $digits = '353'.substr($digits, 1);
        }
        if (str_starts_with($digits, '3530')) {
            $digits = '353'.substr($digits, 4);
        }

        return str_starts_with($digits, '353') && strlen($digits) >= 11 && strlen($digits) <= 12 ? $digits : null;
    }
}

if (! function_exists('tel_href')) {
    function tel_href(?string $number): string
    {
        $digits = ie_digits($number);

        return $digits ? 'tel:+'.$digits : 'tel:'.preg_replace('/[^\d+]/', '', (string) $number);
    }
}

if (! function_exists('format_phone')) {
    /**
     * +353 91 381 111, +353 87 123 4567, +353 1 234 5678: international format, grouped the
     * way Irish numbers are read. Mobiles (8x) and Dublin (1) have their own grouping.
     */
    function format_phone(?string $number): string
    {
        $d = ie_digits($number);
        if (! $d) {
            return (string) $number;
        }
        $n = substr($d, 3);
        if ($n[0] === '1') {
            return '+353 1 '.substr($n, 1, 3).' '.substr($n, 4);
        }
        if ($n[0] === '8') {
            return '+353 '.substr($n, 0, 2).' '.substr($n, 2, 3).' '.substr($n, 5);
        }

        return '+353 '.substr($n, 0, 2).' '.substr($n, 2, 3).' '.substr($n, 5);
    }
}

if (! function_exists('input_string')) {
    /** A request value as a string. Arrays (?type[]=x) and other junk become ''. */
    function input_string(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (! function_exists('lines_to_array')) {
    /**
     * Textarea with one item per line, bullets optional, to a clean list.
     *
     * @return list<string>
     */
    function lines_to_array(?string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
        $clean = array_map(fn (string $l) => trim((string) preg_replace('/^(?:[-*]|•)\s*/u', '', $l)), $lines);

        return array_values(array_filter($clean, fn (string $l) => $l !== ''));
    }
}

if (! function_exists('compact_number')) {
    /** 1,284 → 1.3K, used on stat tiles. */
    function compact_number(int|float $n): string
    {
        return match (true) {
            abs($n) >= 1_000_000 => round($n / 1_000_000, 1).'M',
            abs($n) >= 10_000 => round($n / 1_000).'K',
            default => number_format($n),
        };
    }
}

if (! function_exists('photo')) {
    /**
     * Responsive <img> attributes for a photo in public/img (WebP at 480/960/1920 wide).
     *
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    function photo(string $key): array
    {
        $meta = config('allpro.photos.'.$key) ?? ['w' => 1920, 'h' => 1280];
        $url = fn (int $w) => asset("img/{$key}-{$w}.webp");

        return [
            'src' => $url(960),
            'srcset' => $url(480).' 480w, '.$url(960).' 960w, '.$url(1920).' 1920w',
            'width' => (int) $meta['w'],
            'height' => (int) $meta['h'],
        ];
    }
}

if (! function_exists('file_size')) {
    /** 2.4 MB, 380 KB */
    function file_size(?int $bytes): string
    {
        $bytes = (int) $bytes;

        return $bytes >= 1_048_576 ? round($bytes / 1_048_576, 1).' MB' : max(1, (int) round($bytes / 1024)).' KB';
    }
}

if (! function_exists('eur')) {
    /** €12,500 or €1,250.50; null stays null. */
    function eur(int|float|null $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return '€'.number_format((float) $amount, fmod((float) $amount, 1.0) === 0.0 ? 0 : 2, '.', ',');
    }
}

if (! function_exists('format_eircode')) {
    /** H91 AB12: upper case with the routing key split off. */
    function format_eircode(?string $code): ?string
    {
        $c = strtoupper((string) preg_replace('/\s+/', '', (string) $code));

        return $c === '' ? null : (strlen($c) === 7 ? substr($c, 0, 3).' '.substr($c, 3) : $c);
    }
}

if (! function_exists('duration_short')) {
    /** 95 minutes → "1 h 35 min", 3000 → "2 d 2 h". */
    function duration_short(int $minutes): string
    {
        $m = abs($minutes);

        return match (true) {
            $m >= 1440 => intdiv($m, 1440).' d'.($m % 1440 >= 60 ? ' '.intdiv($m % 1440, 60).' h' : ''),
            $m >= 60 => intdiv($m, 60).' h'.($m % 60 ? ' '.($m % 60).' min' : ''),
            default => $m.' min',
        };
    }
}
