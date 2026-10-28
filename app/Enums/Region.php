<?php

namespace App\Enums;

/** The four offices. Labels and county lists live in config('allpro.regions'). */
enum Region: string
{
    case Galway = 'galway';
    case Dublin = 'dublin';
    case Limerick = 'limerick';
    case Cork = 'cork';

    public function label(): string
    {
        return (string) allpro('regions.'.$this->value.'.label');
    }

    public function code(): string
    {
        return (string) allpro('regions.'.$this->value.'.code');
    }

    /** The office that covers a county ("Kerry" → Cork). Galway when the county is unknown. */
    public static function forCounty(?string $county): self
    {
        foreach (self::cases() as $region) {
            if (in_array($county, (array) allpro('regions.'.$region->value.'.counties'), true)) {
                return $region;
            }
        }

        return self::Galway;
    }

    /** @return array<string, string> value => label, for selects */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }
}
