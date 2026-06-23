<?php

namespace Database\Factories;

use App\Models\PayComponent;
use App\Models\Payslip;
use App\Models\PayslipItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayslipItem>
 */
class PayslipItemFactory extends Factory
{
    protected $model = PayslipItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payslip_id' => Payslip::factory(),
            'pay_component_id' => PayComponent::factory(),
            'label' => fake()->words(2, true),
            'amount' => '500.00',
        ];
    }
}
