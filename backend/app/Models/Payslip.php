<?php

namespace App\Models;

use App\Enums\PayslipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period',
        'gross',
        'deductions',
        'net',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'gross' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net' => 'decimal:2',
            'status' => PayslipStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeInformation::class, 'employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }
}
