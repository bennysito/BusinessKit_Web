<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    //

    protected $fillable = ['name'];

    public function employees()
    {
        return $this->hasMany(EmployeeInformation::class, 'department_id', 'id');
    }

    // TODO might add positions available in this department
}
