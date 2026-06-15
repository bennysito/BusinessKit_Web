<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmployeeInformationController extends Controller
{
    //

    public function index()
    {
        return response()->json([
            'employee_information' => \App\Models\EmployeeInformation::all(),
        ]);
    }


    public function getEmployeeInformation($employee_id)
    {
        $employeeInformation = \App\Models\EmployeeInformation::where('employee_id', $employee_id)->first();

        if (!$employeeInformation) {
            return response()->json([
                'message' => 'Employee information not found',
            ], 404);
        }

        return response()->json([
            'employee_information' => $employeeInformation,
        ]);
    }
}
