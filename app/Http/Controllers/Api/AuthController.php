<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $loginRequest = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $user = \App\Models\User::where('email', $loginRequest['email'])->first();
        
        if (!$user) 
            return response()->json(['message' => 'Email not found'], 404);

        if(!Hash::check($loginRequest['password'], $user->password))
            return response()->json(['message' => 'Invalid password'], 401);

        $accessToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $accessToken,
            'user' => $user,
        ]);

    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful']);
    }
}
