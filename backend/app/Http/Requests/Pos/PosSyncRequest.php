<?php

namespace App\Http\Requests\Pos;

use App\Enums\SaleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PosSyncRequest extends FormRequest
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
            'sales' => ['required', 'array', 'min:1'],
            'sales.*.external_reference' => ['required', 'string', 'max:255', 'distinct'],
            'sales.*.sold_at' => ['required', 'date'],
            'sales.*.cashier' => ['nullable', 'string', 'max:255'],
            'sales.*.payment_method' => ['required', 'string', 'max:255'],
            'sales.*.status' => ['nullable', new Enum(SaleStatus::class)],
            'sales.*.subtotal' => ['required', 'numeric', 'min:0'],
            'sales.*.tax' => ['required', 'numeric', 'min:0'],
            'sales.*.total' => ['required', 'numeric', 'min:0'],
            'sales.*.items' => ['required', 'array', 'min:1'],
            'sales.*.items.*.sku' => ['required', 'string', 'max:255'],
            'sales.*.items.*.quantity' => ['required', 'integer', 'min:1'],
            'sales.*.items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
