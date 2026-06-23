<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),
            'employee_profile' => $this->whenLoaded('employeeInformation', function (): ?array {
                if (! $this->employeeInformation) {
                    return null;
                }

                return [
                    'id' => $this->employeeInformation->id,
                    'employee_id' => $this->employeeInformation->employee_id,
                    'first_name' => $this->employeeInformation->first_name,
                    'last_name' => $this->employeeInformation->last_name,
                    'email' => $this->employeeInformation->email,
                    'department' => $this->employeeInformation->department?->name,
                    'position' => $this->employeeInformation->position?->name,
                    'employment_status' => $this->employeeInformation->employment_status?->value,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
