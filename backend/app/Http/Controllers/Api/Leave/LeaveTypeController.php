<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveTypeRequest;
use App\Http\Requests\Leave\UpdateLeaveTypeRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LeaveTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', LeaveType::class);

        $leaveTypes = LeaveType::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return LeaveTypeResource::collection($leaveTypes);
    }

    public function store(StoreLeaveTypeRequest $request): LeaveTypeResource
    {
        $this->authorize('create', LeaveType::class);

        $leaveType = LeaveType::query()->create([
            ...$request->validated(),
            'is_paid' => $request->boolean('is_paid', true),
        ]);

        return LeaveTypeResource::make($leaveType);
    }

    public function show(LeaveType $leaveType): LeaveTypeResource
    {
        $this->authorize('view', $leaveType);

        return LeaveTypeResource::make($leaveType);
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): LeaveTypeResource
    {
        $this->authorize('update', $leaveType);

        $leaveType->update($request->validated());

        return LeaveTypeResource::make($leaveType->refresh());
    }

    public function destroy(LeaveType $leaveType): Response
    {
        $this->authorize('delete', $leaveType);

        $leaveType->delete();

        return response()->noContent();
    }
}
