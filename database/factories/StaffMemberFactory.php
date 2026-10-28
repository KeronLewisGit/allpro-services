<?php

namespace Database\Factories;

use App\Enums\Region;
use App\Enums\RoleType;
use App\Enums\VettingStatus;
use App\Models\StaffMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StaffMember> */
class StaffMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_no' => 'E'.fake()->unique()->numerify('#####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'role_type' => RoleType::SecurityOfficer,
            'region' => Region::Galway,
            'phone' => '086 '.fake()->numerify('### ####'),
            'started_on' => today()->subYear(),
            'active' => true,
            'psa_licence_number' => fake()->numerify('#######'),
            'psa_licence_expires_on' => today()->addYear(),
            'vetting_status' => VettingStatus::Cleared,
            'vetting_cleared_on' => today()->subYear(),
            'rtw_checked_on' => today()->subYear(),
        ];
    }

    public function expiringIn(int $days): static
    {
        return $this->state(fn () => ['psa_licence_expires_on' => today()->addDays($days)]);
    }
}
