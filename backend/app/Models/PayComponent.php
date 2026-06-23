<?php

namespace App\Models;

use App\Enums\PayComponentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'amount',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'type' => PayComponentType::class,
            'amount' => 'decimal:2',
            'percentage' => 'decimal:2',
        ];
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(
            EmployeeInformation::class,
            'employee_pay_components',
            'pay_component_id',
            'employee_id',
        )->withTimestamps();
    }

    public function payslipItems(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }
}
