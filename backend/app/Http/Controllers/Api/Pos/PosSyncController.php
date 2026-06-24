<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosSyncRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\PosSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class PosSyncController extends Controller
{
    public function sync(PosSyncRequest $request, PosSyncService $posSyncService): JsonResponse
    {
        $this->authorize('sync', Sale::class);

        if (! $request->user()->tokenCan('pos.sync')) {
            abort(403, 'The current token does not have the required pos.sync ability.');
        }

        $result = $posSyncService->sync($request->input('sales'), $request->user());

        return response()->json([
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'sales' => SaleResource::collection(collect($result['synced_sales'])),
        ], 201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Sale::class);

        $sales = Sale::query()
            ->with('items')
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->string('payment_method')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('sold_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('sold_at', '<=', $request->date('date_to')))
            ->latest('sold_at')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return SaleResource::collection($sales);
    }

    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Sale::class);

        $groupBy = $request->string('group_by')->toString() ?: 'day';
        $groupBy = in_array($groupBy, ['day', 'product', 'payment_method'], true) ? $groupBy : 'day';

        $sales = Sale::query()
            ->with('items')
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('sold_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('sold_at', '<=', $request->date('date_to')))
            ->get();

        $summary = match ($groupBy) {
            'product' => $sales
                ->flatMap->items
                ->groupBy('sku')
                ->map(fn (Collection $items, string $sku): array => [
                    'sku' => $sku,
                    'quantity' => $items->sum('quantity'),
                    'line_total' => number_format($items->sum(fn ($item): float => (float) $item->line_total), 2, '.', ''),
                ])
                ->values(),
            'payment_method' => $sales
                ->groupBy('payment_method')
                ->map(fn (Collection $items, string $paymentMethod): array => [
                    'payment_method' => $paymentMethod,
                    'sales_count' => $items->count(),
                    'total' => number_format($items->sum(fn ($sale): float => (float) $sale->total), 2, '.', ''),
                ])
                ->values(),
            default => $sales
                ->groupBy(fn (Sale $sale): string => $sale->sold_at->toDateString())
                ->map(fn (Collection $items, string $day): array => [
                    'day' => $day,
                    'sales_count' => $items->count(),
                    'total' => number_format($items->sum(fn ($sale): float => (float) $sale->total), 2, '.', ''),
                ])
                ->values(),
        };

        return response()->json([
            'data' => $summary,
        ]);
    }
}
