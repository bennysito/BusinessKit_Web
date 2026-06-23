<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayComponentRequest;
use App\Http\Requests\Payroll\UpdatePayComponentRequest;
use App\Http\Resources\PayComponentResource;
use App\Models\PayComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PayComponentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PayComponent::class);

        $payComponents = PayComponent::query()
            ->with('employees')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return PayComponentResource::collection($payComponents);
    }

    public function store(StorePayComponentRequest $request): PayComponentResource
    {
        $this->authorize('create', PayComponent::class);

        $payComponent = PayComponent::query()->create($request->safe()->except('employee_ids'));

        if ($request->filled('employee_ids')) {
            $payComponent->employees()->sync($request->input('employee_ids'));
        }

        return PayComponentResource::make($payComponent->load('employees'));
    }

    public function show(PayComponent $payComponent): PayComponentResource
    {
        $this->authorize('view', $payComponent);

        return PayComponentResource::make($payComponent->load('employees'));
    }

    public function update(UpdatePayComponentRequest $request, PayComponent $payComponent): PayComponentResource
    {
        $this->authorize('update', $payComponent);

        $payComponent->update($request->safe()->except('employee_ids'));

        if ($request->exists('employee_ids')) {
            $payComponent->employees()->sync($request->input('employee_ids', []));
        }

        return PayComponentResource::make($payComponent->refresh()->load('employees'));
    }

    public function destroy(PayComponent $payComponent): Response
    {
        $this->authorize('delete', $payComponent);

        $payComponent->delete();

        return response()->noContent();
    }
}
