<?php

namespace Database\Factories;

use App\Enums\Region;
use App\Models\ClientAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ClientAccount> */
class ClientAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organisation' => fake()->company().' (sample)',
            'site_name' => 'Main building',
            'region' => Region::Galway,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'services' => ['cleaning'],
            'link_expires_at' => now()->addDays(90),
        ];
    }
}
