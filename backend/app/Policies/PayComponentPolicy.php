<?php

namespace App\Policies;

use App\Models\PayComponent;
use App\Models\User;

class PayComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.manage');
    }

    public function view(User $user, PayComponent $payComponent): bool
    {
        return $user->can('payroll.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.manage');
    }

    public function update(User $user, PayComponent $payComponent): bool
    {
        return $user->can('payroll.manage');
    }

    public function delete(User $user, PayComponent $payComponent): bool
    {
        return $user->can('payroll.manage');
    }
}
