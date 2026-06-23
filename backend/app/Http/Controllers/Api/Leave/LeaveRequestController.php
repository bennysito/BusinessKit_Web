<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\ChangeLeaveRequestStatusRequest;
use App\Http\Requests\Leave\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $leaveRequests = LeaveRequest::query()
            ->with(['employee', 'leaveType', 'approver'])
            ->when(! $request->user()->can('leave.approve'), function ($query) use ($request): void {
                $employeeId = $request->user()->employeeInformation?->id;
                $query->where('employee_id', $employeeId ?? 0);
            })
            ->when($request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('start_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('end_date', '<=', $request->date('date_to')))
            ->latest('start_date')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return LeaveRequestResource::collection($leaveRequests);
    }

    public function store(StoreLeaveRequestRequest $request, LeaveService $leaveService): LeaveRequestResource
    {
        $this->authorize('create', LeaveRequest::class);

        $leaveRequest = $leaveService->submit($request->user()->employeeInformation, $request->validated());

        return LeaveRequestResource::make($leaveRequest->load(['employee', 'leaveType', 'approver']));
    }

    public function show(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->authorize('view', $leaveRequest->load('employee'));

        return LeaveRequestResource::make($leaveRequest->load(['employee', 'leaveType', 'approver']));
    }

    public function cancel(
        ChangeLeaveRequestStatusRequest $request,
        LeaveRequest $leaveRequest,
        LeaveService $leaveService,
    ): LeaveRequestResource {
        $this->authorize('cancel', $leaveRequest->load('employee'));

        $leaveRequest = $leaveService->cancel($leaveRequest);

        return LeaveRequestResource::make($leaveRequest->load(['employee', 'leaveType', 'approver']));
    }

    public function approve(
        ChangeLeaveRequestStatusRequest $request,
        LeaveRequest $leaveRequest,
        LeaveService $leaveService,
    ): LeaveRequestResource {
        $this->authorize('approve', $leaveRequest->load('employee'));

        $leaveRequest = $leaveService->approve($leaveRequest, $request->user());

        return LeaveRequestResource::make($leaveRequest->load(['employee', 'leaveType', 'approver']));
    }

    public function reject(
        ChangeLeaveRequestStatusRequest $request,
        LeaveRequest $leaveRequest,
        LeaveService $leaveService,
    ): LeaveRequestResource {
        $this->authorize('reject', $leaveRequest->load('employee'));

        $leaveRequest = $leaveService->reject($leaveRequest, $request->user());

        return LeaveRequestResource::make($leaveRequest->load(['employee', 'leaveType', 'approver']));
    }
}
