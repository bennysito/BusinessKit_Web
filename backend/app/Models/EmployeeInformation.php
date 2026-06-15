<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeInformation extends Model
{
    //

    protected $fillable = [
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'address',
        'position',
        'department',
        'date_of_hire',
    ];



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
