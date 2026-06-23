<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
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
        $leaveType = $this->route('leaveType');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('leave_types', 'name')->ignore($leaveType)],
            'default_days' => ['sometimes', 'integer', 'min:0'],
            'is_paid' => ['sometimes', 'boolean'],
        ];
    }
}
