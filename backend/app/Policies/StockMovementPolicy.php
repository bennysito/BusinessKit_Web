<?php

namespace App\Policies;

use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.manage');
    }
}
