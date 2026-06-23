<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StorePositionRequest;
use App\Http\Requests\Hr\UpdatePositionRequest;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PositionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Position::class);

        $positions = Position::query()
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function ($positionQuery) use ($search): void {
                    $positionQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return PositionResource::collection($positions);
    }

    public function store(StorePositionRequest $request): PositionResource
    {
        $this->authorize('create', Position::class);

        $position = Position::query()->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return PositionResource::make($position);
    }

    public function show(Position $position): PositionResource
    {
        $this->authorize('view', $position);

        return PositionResource::make($position);
    }

    public function update(UpdatePositionRequest $request, Position $position): PositionResource
    {
        $this->authorize('update', $position);

        $position->update($request->validated());

        return PositionResource::make($position->refresh());
    }

    public function destroy(Position $position): Response
    {
        $this->authorize('delete', $position);

        $position->delete();

        return response()->noContent();
    }
}
