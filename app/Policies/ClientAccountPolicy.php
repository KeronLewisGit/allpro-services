<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\ClientAccount;
use App\Models\User;

/** Staff see the areas their role works in (Role::areas()); administrators see everything. */
class ClientAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::ServiceDesk);
    }

    public function view(User $user, ClientAccount $account): bool
    {
        return $user->works(Area::ServiceDesk);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::ServiceDesk);
    }

    public function update(User $user, ClientAccount $account): bool
    {
        return $user->works(Area::ServiceDesk);
    }
}
