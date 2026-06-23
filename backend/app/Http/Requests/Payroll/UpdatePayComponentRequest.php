<?php

namespace App\Http\Requests\Payroll;

use App\Enums\PayComponentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayComponentRequest extends FormRequest
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
        $payComponent = $this->route('payComponent');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('pay_components', 'name')->ignore($payComponent)],
            'type' => ['sometimes', Rule::enum(PayComponentType::class)],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0'],
            'employee_ids' => ['sometimes', 'array'],
            'employee_ids.*' => ['integer', 'exists:employee_information,id'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $hasAmount = $this->exists('amount') && $this->input('amount') !== null;
                $hasPercentage = $this->exists('percentage') && $this->input('percentage') !== null;

                if ($hasAmount && $hasPercentage) {
                    $validator->errors()->add('amount', 'Provide either an amount or a percentage, but not both.');
                }
            },
        ];
    }
}
