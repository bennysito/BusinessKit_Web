<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json([
            'roles' => \App\Models\Role::all(),
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
        ]);

        $role = \App\Models\Role::create($validated);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role,
        ], 201);
    }
}
