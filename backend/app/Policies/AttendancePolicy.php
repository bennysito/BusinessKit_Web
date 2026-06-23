<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.manage');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $this->canManageAll($user)
            || $attendance->employee?->user_id === $user->id;
    }

    public function clockIn(User $user): bool
    {
        return $user->can('attendance.manage')
            && $user->employeeInformation()->exists();
    }

    public function clockOut(User $user): bool
    {
        return $this->clockIn($user);
    }

    private function canManageAll(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'hr', 'manager']);
    }
}
