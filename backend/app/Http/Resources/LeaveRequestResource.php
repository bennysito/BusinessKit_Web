<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
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
            'leave_type' => $this->whenLoaded('leaveType', fn (): array => [
                'id' => $this->leaveType->id,
                'name' => $this->leaveType->name,
                'is_paid' => $this->leaveType->is_paid,
            ]),
            'approver' => $this->whenLoaded('approver', fn (): ?array => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
                'email' => $this->approver->email,
            ] : null),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'days' => $this->days,
            'reason' => $this->reason,
            'status' => $this->status?->value,
            'decided_at' => $this->decided_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
