<?php

namespace App\Http\Requests\Payroll;

use App\Enums\PayComponentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayComponentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:pay_components,name'],
            'type' => ['required', Rule::enum(PayComponentType::class)],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employee_information,id'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $amount = $this->input('amount');
                $percentage = $this->input('percentage');

                if (($amount === null && $percentage === null) || ($amount !== null && $percentage !== null)) {
                    $validator->errors()->add('amount', 'Provide either an amount or a percentage, but not both.');
                }
            },
        ];
    }
}
