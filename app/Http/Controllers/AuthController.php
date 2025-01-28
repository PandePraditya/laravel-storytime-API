<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while registering the user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
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

            // Create a token for the user
            $token = $user->createToken('Token')->plainTextToken;

            return response()->json([
                'message' => 'User logged in successfully.',
                'user' => $user,
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while logging in.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Logout a user
    public function logout(Request $request)
    {
        try {
            // Get the token from the request
            $token = $request->bearerToken();

            // Check if the token is provided
            if (!$token) {
                return response()->json([
                    'message' => 'Sorry, you must provide login token first.',
                ], 400);
            }

            // Find the user associated with the token
            $user = User::whereHas('tokens', function ($query) use ($token) {
                $query->where('id', $token);
            })->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized. Token is invalid or user not found.'
                ], 401);
            }

            // Revoke all tokens for the user
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Logged out successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while logging out.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
