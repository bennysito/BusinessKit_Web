<?php

namespace App\Policies;

use App\Models\Payslip;
use App\Models\User;

class PayslipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.manage');
    }

    public function view(User $user, Payslip $payslip): bool
    {
        return $user->can('payroll.manage');
    }

    public function generate(User $user): bool
    {
        return $user->can('payroll.manage');
    }

    public function markPaid(User $user, Payslip $payslip): bool
    {
        return $user->can('payroll.manage');
    }
}
