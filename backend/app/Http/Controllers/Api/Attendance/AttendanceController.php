<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\ClockInRequest;
use App\Http\Requests\Attendance\ClockOutRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class AttendanceController extends Controller
{
    public function clockIn(ClockInRequest $request, AttendanceService $attendanceService): AttendanceResource
    {
        $this->authorize('clockIn', Attendance::class);

        $attendance = $attendanceService->clockIn(
            $request->user()->employeeInformation,
            $request->string('notes')->toString() ?: null,
        );

        return AttendanceResource::make($attendance->load('employee'));
    }

    public function clockOut(ClockOutRequest $request, AttendanceService $attendanceService): AttendanceResource
    {
        $this->authorize('clockOut', Attendance::class);

        $attendance = $attendanceService->clockOut(
            $request->user()->employeeInformation,
            $request->string('notes')->toString() ?: null,
        );

        return AttendanceResource::make($attendance->load('employee'));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Attendance::class);

        $attendances = Attendance::query()
            ->with('employee')
            ->when(! $request->user()->hasAnyRole(['admin', 'hr', 'manager']), function ($query) use ($request): void {
                $employeeId = $request->user()->employeeInformation?->id;
                $query->where('employee_id', $employeeId ?? 0);
            })
            ->when($request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('date', '<=', $request->date('date_to')))
            ->latest('date')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return AttendanceResource::collection($attendances);
    }

    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        $groupBy = $request->string('group_by')->toString() ?: 'daily';
        $groupBy = in_array($groupBy, ['daily', 'monthly'], true) ? $groupBy : 'daily';

        $records = Attendance::query()
            ->when(! $request->user()->hasAnyRole(['admin', 'hr', 'manager']), function ($query) use ($request): void {
                $employeeId = $request->user()->employeeInformation?->id;
                $query->where('employee_id', $employeeId ?? 0);
            })
            ->when($request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('date', '<=', $request->date('date_to')))
            ->orderBy('date')
            ->get();

        $summary = $records
            ->groupBy(fn (Attendance $attendance): string => $groupBy === 'monthly'
                ? $attendance->date->format('Y-m')
                : $attendance->date->toDateString())
            ->map(fn (Collection $items, string $period): array => [
                'period' => $period,
                'entries' => $items->count(),
                'hours_worked' => number_format($items->sum(fn (Attendance $attendance): float => (float) ($attendance->hours_worked ?? 0)), 2, '.', ''),
                'status_counts' => [
                    'present' => $items->where('status', AttendanceStatus::Present)->count(),
                    'late' => $items->where('status', AttendanceStatus::Late)->count(),
                    'absent' => $items->where('status', AttendanceStatus::Absent)->count(),
                    'leave' => $items->where('status', AttendanceStatus::Leave)->count(),
                ],
            ])
            ->values();

        return response()->json([
            'data' => $summary,
        ]);
    }
}
