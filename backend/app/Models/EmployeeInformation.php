<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeInformation extends Model
{
    //

    protected $fillable = [
        'user_id',
        'employee_id',
        'department_id',
        'position_id',
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

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}
