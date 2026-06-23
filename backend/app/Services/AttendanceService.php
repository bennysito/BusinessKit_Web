<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\EmployeeInformation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function clockIn(EmployeeInformation $employee, ?string $notes = null): Attendance
    {
        return DB::transaction(function () use ($employee, $notes): Attendance {
            $now = now();
            $date = $now->toDateString();

            $attendance = Attendance::query()
                ->lockForUpdate()
                ->firstOrNew([
                    'employee_id' => $employee->id,
                    'date' => $date,
                ]);

            if ($attendance->exists && $attendance->clock_in !== null) {
                throw ValidationException::withMessages([
                    'clock_in' => ['You have already clocked in for today.'],
                ]);
            }

            $attendance->fill([
                'clock_in' => $now,
                'status' => $this->resolveStatusForClockIn($now),
                'notes' => $notes,
            ]);
            $attendance->save();

            return $attendance->refresh();
        });
    }

    public function clockOut(EmployeeInformation $employee, ?string $notes = null): Attendance
    {
        return DB::transaction(function () use ($employee, $notes): Attendance {
            $attendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', now()->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $attendance || $attendance->clock_in === null) {
                throw ValidationException::withMessages([
                    'clock_out' => ['You must clock in before clocking out.'],
                ]);
            }

            if ($attendance->clock_out !== null) {
                throw ValidationException::withMessages([
                    'clock_out' => ['You have already clocked out for today.'],
                ]);
            }

            $clockOutTime = now();
            $hoursWorked = round($attendance->clock_in->diffInMinutes($clockOutTime) / 60, 2);

            $attendance->update([
                'clock_out' => $clockOutTime,
                'hours_worked' => number_format($hoursWorked, 2, '.', ''),
                'notes' => $notes ?? $attendance->notes,
            ]);

            return $attendance->refresh();
        });
    }

    private function resolveStatusForClockIn(Carbon $clockIn): AttendanceStatus
    {
        $scheduledStart = Carbon::parse($clockIn->toDateString().' '.config('hrms.attendance.start_time', '09:00'));

        return $clockIn->gt($scheduledStart)
            ? AttendanceStatus::Late
            : AttendanceStatus::Present;
    }
}
