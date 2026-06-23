<?php

namespace App\Http\Requests\Inventory;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'integer'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $type = $this->input('type');
                $quantity = (int) $this->input('quantity');

                if (in_array($type, ['in', 'out'], true) && $quantity <= 0) {
                    $validator->errors()->add('quantity', 'Inbound and outbound stock movements require a positive quantity.');
                }

                if ($type === 'adjustment' && $quantity === 0) {
                    $validator->errors()->add('quantity', 'Adjustment stock movements require a non-zero quantity.');
                }
            },
        ];
    }
}
