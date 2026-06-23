<?php

namespace App\Services;

use App\Enums\PayComponentType;
use App\Enums\PayslipStatus;
use App\Models\EmployeeInformation;
use App\Models\PayComponent;
use App\Models\Payslip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function generate(EmployeeInformation $employee, string $period): Payslip
    {
        if ($employee->salary === null) {
            throw ValidationException::withMessages([
                'employee_id' => ['The selected employee does not have a base salary configured.'],
            ]);
        }

        return DB::transaction(function () use ($employee, $period): Payslip {
            $employee->loadMissing('payComponents');

            $payslip = Payslip::query()->firstOrNew([
                'employee_id' => $employee->id,
                'period' => $period,
            ]);

            if ($payslip->exists && $payslip->status === PayslipStatus::Paid) {
                throw ValidationException::withMessages([
                    'period' => ['Cannot regenerate a payslip that has already been marked as paid.'],
                ]);
            }

            $baseSalary = (float) $employee->salary;
            $gross = $baseSalary;
            $deductions = 0.0;
            $items = [
                [
                    'pay_component_id' => null,
                    'label' => 'Base Salary',
                    'amount' => number_format($baseSalary, 2, '.', ''),
                ],
            ];

            $employee->payComponents->each(function (PayComponent $component) use (&$deductions, &$gross, &$items, $baseSalary): void {
                $componentAmount = $this->resolveComponentAmount($component, $baseSalary);

                if ($component->type === PayComponentType::Earning) {
                    $gross += $componentAmount;
                } else {
                    $deductions += $componentAmount;
                }

                $items[] = [
                    'pay_component_id' => $component->id,
                    'label' => $component->name,
                    'amount' => number_format($componentAmount, 2, '.', ''),
                ];
            });

            $payslip->fill([
                'gross' => number_format($gross, 2, '.', ''),
                'deductions' => number_format($deductions, 2, '.', ''),
                'net' => number_format($gross - $deductions, 2, '.', ''),
                'status' => PayslipStatus::Draft,
            ]);
            $payslip->save();

            $payslip->items()->delete();
            $payslip->items()->createMany($items);

            return $payslip->refresh()->load(['employee', 'items.payComponent']);
        });
    }

    public function markPaid(Payslip $payslip): Payslip
    {
        if ($payslip->status === PayslipStatus::Paid) {
            throw ValidationException::withMessages([
                'status' => ['This payslip is already marked as paid.'],
            ]);
        }

        $payslip->update([
            'status' => PayslipStatus::Paid,
        ]);

        return $payslip->refresh()->load(['employee', 'items.payComponent']);
    }

    private function resolveComponentAmount(PayComponent $component, float $baseSalary): float
    {
        if ($component->amount !== null) {
            return (float) $component->amount;
        }

        return round($baseSalary * ((float) $component->percentage / 100), 2);
    }
}
