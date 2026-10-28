<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\ServiceTicket;
use App\Models\User;

/** Staff see the areas their role works in (Role::areas()); administrators see everything. */
class ServiceTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::ServiceDesk);
    }

    public function view(User $user, ServiceTicket $ticket): bool
    {
        return $user->works(Area::ServiceDesk);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::ServiceDesk);
    }

    public function update(User $user, ServiceTicket $ticket): bool
    {
        return $user->works(Area::ServiceDesk);
    }

    /** Photos from client sites: operations and administrators only. */
    public function openPhoto(User $user, ServiceTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
