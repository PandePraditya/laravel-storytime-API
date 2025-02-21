<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username, // Save the username
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash the password
        ]);

        // Create a token for the user
        $token = $user->createToken('Token')->plainTextToken;

        return response()->json([
            'message' => 'User  registered successfully.',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        // Retrieve the login credentials
        $username_or_email = $request->input('username_or_email');
        $password = $request->input('password');

        // Find the user by email or username
        $user = User::where('email', $username_or_email)
            ->orWhere('username', $username_or_email)
            ->first();

        // Check if the user exists and the password is correct
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        // Create a token for the user using sanctum
        $token = $user->createToken('Token')->plainTextToken;

        return response()->json([
            'message' => 'User logged in successfully.',
            'user' => $user,
            'token' => $token
        ]);
    }

    // Logout a user
    public function logout(Request $request)
    {
        // Get the token from the request
        $token = $request->bearerToken();

        // Check if the token is provided
        if (!$token) {
            return response()->json([
                'message' => 'Sorry, you must login first.',
            ], 400);
        }

        // Get the authenticated user using Sanctum
        $user = $request->user();

        // If user isn't authenticated, return unauthorized
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Token is invalid or user not found.',
            ], 401);
        }

        // Revoke the current token
        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }
}
