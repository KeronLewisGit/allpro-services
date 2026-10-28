<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\Region;
use App\Enums\RoleType;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<JobPosting> */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        $title = 'Cleaning operative '.fake()->unique()->numberBetween(1, 99999);

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'role_type' => RoleType::CleaningOperative,
            'region' => Region::Galway,
            'employment_type' => EmploymentType::PartTime,
            'status' => JobStatus::Open,
            'location' => 'Oranmore, Co. Galway',
            'hours' => 'Mon–Fri, 6 am – 9 am',
            'openings' => 2,
            'summary' => 'Early-morning office cleaning.',
            'duties' => "Vacuum and mop\nEmpty bins",
            'requirements' => 'Reliable and punctual',
        ];
    }

    public function security(): static
    {
        return $this->state(fn () => ['role_type' => RoleType::SecurityOfficer, 'title' => 'Security officer '.fake()->unique()->numberBetween(1, 99999)])
            ->afterMaking(fn (JobPosting $j) => $j->slug = Str::slug($j->title));
    }
}
