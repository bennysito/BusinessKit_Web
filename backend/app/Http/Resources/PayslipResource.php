<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee' => $this->whenLoaded('employee', fn (): array => [
                'id' => $this->employee->id,
                'employee_id' => $this->employee->employee_id,
                'full_name' => trim($this->employee->first_name.' '.$this->employee->last_name),
            ]),
            'period' => $this->period,
            'gross' => $this->gross,
            'deductions' => $this->deductions,
            'net' => $this->net,
            'status' => $this->status?->value,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'label' => $item->label,
                'amount' => $item->amount,
                'pay_component_id' => $item->pay_component_id,
            ])->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
