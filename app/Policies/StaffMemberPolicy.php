<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\StaffMember;
use App\Models\User;

/** Staff see the areas their role works in (Role::areas()); administrators see everything. */
class StaffMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Staff);
    }

    public function view(User $user, StaffMember $staff): bool
    {
        return $user->works(Area::Staff);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::Staff);
    }

    public function update(User $user, StaffMember $staff): bool
    {
        return $user->works(Area::Staff);
    }
}
