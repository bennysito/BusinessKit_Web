<?php

namespace App\Policies;

use App\Models\EmployeeInformation;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employees.view');
    }

    public function view(User $user, EmployeeInformation $employee): bool
    {
        return $user->can('employees.manage')
            || ($user->can('employees.view') && $employee->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can('employees.manage');
    }

    public function update(User $user, EmployeeInformation $employee): bool
    {
        return $user->can('employees.manage');
    }

    public function delete(User $user, EmployeeInformation $employee): bool
    {
        return $user->can('employees.manage');
    }
}
