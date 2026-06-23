<?php

namespace App\Http\Requests\Hr;

use App\Enums\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id', 'unique:employee_information,user_id'],
            'employee_id' => ['required', 'string', 'max:255', 'unique:employee_information,employee_id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:employee_information,email'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'date_of_hire' => ['nullable', 'date'],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
