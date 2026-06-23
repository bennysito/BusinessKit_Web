<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'date' => $this->date?->toDateString(),
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'status' => $this->status?->value,
            'hours_worked' => $this->hours_worked,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
