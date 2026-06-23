<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim($this->first_name.' '.$this->last_name),
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'date_of_hire' => $this->date_of_hire?->toDateString(),
            'employment_status' => $this->employment_status?->value,
            'salary' => $this->salary,
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'department' => $this->whenLoaded('department', fn (): ?array => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null),
            'position' => $this->whenLoaded('position', fn (): ?array => $this->position ? [
                'id' => $this->position->id,
                'name' => $this->position->name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
