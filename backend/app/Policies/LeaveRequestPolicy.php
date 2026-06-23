<?php

namespace App\Policies;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leave.request') || $user->can('leave.approve');
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('leave.approve')
            || $leaveRequest->employee?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('leave.request')
            && $user->employeeInformation()->exists();
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->employee?->user_id === $user->id
            && in_array($leaveRequest->status, [LeaveStatus::Pending, LeaveStatus::Approved], true);
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('leave.approve')
            && $leaveRequest->employee?->user_id !== $user->id;
    }

    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->approve($user, $leaveRequest);
    }
}
