<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    //

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = $request->user();

        if (Auth::attempt($credentials)) {

            $abilities = match ($user->role) {
                'admin' => ['*'],
                'superadmin' => ['*'],
                'user' => ['read', 'user-dashboard'],
                default => ['read'],
            };

            return response()->json([
                'message' => 'Login successful',
                'token' => $user->createToken('auth_token', $abilities, now()->addDay())->plainTextToken,
            ]);
        }

        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }
}
