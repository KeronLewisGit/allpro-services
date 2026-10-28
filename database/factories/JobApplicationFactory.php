<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Enums\Experience;
use App\Enums\Region;
use App\Enums\RightToWork;
use App\Enums\RoleType;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobApplication> */
class JobApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'role_type' => RoleType::CleaningOperative,
            'region' => Region::Galway,
            'status' => ApplicationStatus::New,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '087 '.fake()->numerify('### ####'),
            'email' => fake()->safeEmail(),
            'county' => 'Galway',
            'experience' => Experience::OneToThree,
            'right_to_work' => RightToWork::Citizen,
            'availability' => ['mon-early', 'tue-early', 'wed-early'],
            'consented_at' => now(),
        ];
    }

    public function security(int $expiresInDays = 400): static
    {
        return $this->state(fn () => [
            'role_type' => RoleType::SecurityOfficer,
            'psa_licence_number' => fake()->numerify('#######'),
            'psa_licence_expires_on' => today()->addDays($expiresInDays),
        ]);
    }
}
