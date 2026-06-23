<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Models\EmployeeInformation;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function submit(EmployeeInformation $employee, array $attributes): LeaveRequest
    {
        return DB::transaction(function () use ($employee, $attributes): LeaveRequest {
            $startDate = Carbon::parse($attributes['start_date']);
            $endDate = Carbon::parse($attributes['end_date']);
            $days = $this->calculateDays($startDate, $endDate);
            $leaveType = LeaveType::query()->findOrFail($attributes['leave_type_id']);
            $balance = $this->resolveBalance($employee, $leaveType, (int) $startDate->format('Y'));

            if (((float) $balance->entitled - (float) $balance->used) < $days) {
                throw ValidationException::withMessages([
                    'leave_type_id' => ['Insufficient leave balance for this request.'],
                ]);
            }

            return LeaveRequest::query()->create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => number_format($days, 2, '.', ''),
                'reason' => $attributes['reason'],
                'status' => LeaveStatus::Pending,
            ]);
        });
    }

    public function approve(LeaveRequest $leaveRequest, User $approver): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver): LeaveRequest {
            $leaveRequest = LeaveRequest::query()
                ->with(['employee', 'leaveType'])
                ->lockForUpdate()
                ->findOrFail($leaveRequest->id);

            if ($leaveRequest->status !== LeaveStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => ['Only pending leave requests can be approved.'],
                ]);
            }

            $balance = $this->resolveBalance(
                $leaveRequest->employee,
                $leaveRequest->leaveType,
                (int) $leaveRequest->start_date->format('Y'),
            );

            if (((float) $balance->entitled - (float) $balance->used) < (float) $leaveRequest->days) {
                throw ValidationException::withMessages([
                    'status' => ['Insufficient leave balance to approve this request.'],
                ]);
            }

            $balance->update([
                'used' => number_format((float) $balance->used + (float) $leaveRequest->days, 2, '.', ''),
            ]);

            $leaveRequest->update([
                'status' => LeaveStatus::Approved,
                'approver_id' => $approver->id,
                'decided_at' => now(),
            ]);

            return $leaveRequest->refresh();
        });
    }

    public function reject(LeaveRequest $leaveRequest, User $approver): LeaveRequest
    {
        if ($leaveRequest->status !== LeaveStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => ['Only pending leave requests can be rejected.'],
            ]);
        }

        $leaveRequest->update([
            'status' => LeaveStatus::Rejected,
            'approver_id' => $approver->id,
            'decided_at' => now(),
        ]);

        return $leaveRequest->refresh();
    }

    public function cancel(LeaveRequest $leaveRequest): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest): LeaveRequest {
            $leaveRequest = LeaveRequest::query()
                ->with(['employee', 'leaveType'])
                ->lockForUpdate()
                ->findOrFail($leaveRequest->id);

            if (! in_array($leaveRequest->status, [LeaveStatus::Pending, LeaveStatus::Approved], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Only pending or approved leave requests can be cancelled.'],
                ]);
            }

            if ($leaveRequest->status === LeaveStatus::Approved) {
                $balance = $this->resolveBalance(
                    $leaveRequest->employee,
                    $leaveRequest->leaveType,
                    (int) $leaveRequest->start_date->format('Y'),
                );

                $balance->update([
                    'used' => number_format(max(0, (float) $balance->used - (float) $leaveRequest->days), 2, '.', ''),
                ]);
            }

            $leaveRequest->update([
                'status' => LeaveStatus::Cancelled,
                'decided_at' => now(),
            ]);

            return $leaveRequest->refresh();
        });
    }

    public function calculateDays(Carbon $startDate, Carbon $endDate): float
    {
        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => ['The end date must be on or after the start date.'],
            ]);
        }

        return (float) $startDate->diffInDays($endDate) + 1;
    }

    private function resolveBalance(EmployeeInformation $employee, LeaveType $leaveType, int $year): LeaveBalance
    {
        return LeaveBalance::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ],
            [
                'entitled' => number_format((float) $leaveType->default_days, 2, '.', ''),
                'used' => '0.00',
            ],
        );
    }
}
