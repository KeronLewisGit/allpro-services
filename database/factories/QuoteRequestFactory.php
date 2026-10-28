<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuoteRequest> */
class QuoteRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => QuoteStatus::New,
            'services' => ['cleaning'],
            'sector' => 'corporate',
            'organisation' => fake()->company().' (sample)',
            'contact_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '091 '.fake()->numerify('### ###'),
            'site_name' => 'Head office',
            'site_address' => fake()->streetAddress(),
            'town' => 'Galway',
            'county' => 'Galway',
            'site_count' => 1,
            'survey_on' => today()->addWeekdays(5),
            'survey_window' => 'any',
            'consented_at' => now(),
        ];
    }

    public function surveyIn(int $days): static
    {
        return $this->state(fn () => ['survey_on' => today()->addDays($days)]);
    }
}
