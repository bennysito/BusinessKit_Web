<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reports.view');
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->can('reports.view');
    }

    public function sync(User $user): bool
    {
        return $user->can('pos.sync');
    }
}
