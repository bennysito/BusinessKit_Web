<?php

namespace App\Http\Requests\Hr;

use App\Enums\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        $employee = $this->route('employee');

        return [
            'user_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
                Rule::unique('employee_information', 'user_id')->ignore($employee),
            ],
            'employee_id' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('employee_information', 'employee_id')->ignore($employee),
            ],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('employee_information', 'email')->ignore($employee),
            ],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'date_of_hire' => ['nullable', 'date'],
            'employment_status' => ['sometimes', Rule::enum(EmploymentStatus::class)],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
