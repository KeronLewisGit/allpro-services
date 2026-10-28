<?php

namespace Database\Factories;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketType;
use App\Models\ClientAccount;
use App\Models\ServiceTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceTicket> */
class ServiceTicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_account_id' => ClientAccount::factory(),
            'type' => TicketType::Request,
            'category' => TicketCategory::Cleaning,
            'priority' => TicketPriority::Normal,
            'reporter_name' => fake()->name(),
            'reporter_email' => fake()->safeEmail(),
            'description' => 'Please add a deep clean of the second-floor kitchen this week.',
        ];
    }

    public function priority(TicketPriority $p): static
    {
        return $this->state(fn () => ['priority' => $p]);
    }

    /** Logged $minutes ago, so its SLA clocks are already running. */
    public function loggedAgo(int $minutes): static
    {
        return $this->state(fn () => ['created_at' => now()->subMinutes($minutes), 'updated_at' => now()->subMinutes($minutes)]);
    }
}
