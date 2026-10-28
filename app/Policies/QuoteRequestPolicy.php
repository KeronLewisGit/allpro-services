<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\QuoteRequest;
use App\Models\User;

/** Staff see the areas their role works in (Role::areas()); administrators see everything. */
class QuoteRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Quotes);
    }

    public function view(User $user, QuoteRequest $quote): bool
    {
        return $user->works(Area::Quotes);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::Quotes);
    }

    public function update(User $user, QuoteRequest $quote): bool
    {
        return $user->works(Area::Quotes);
    }

    /** Floor plans and tender documents: sales and administrators only. */
    public function openFile(User $user, QuoteRequest $quote): bool
    {
        return $this->view($user, $quote);
    }
}
