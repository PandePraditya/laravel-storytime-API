<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users', // Ensure username is unique
                'email' => 'required|string|email|max:255|unique:users',
                'password' => [
                    'required',
                    'string',
                    'min:8', // Minimum length
                    'confirmed', // Must match the password confirmation
                    'regex:/[0-9]/', // Must contain at least one number
                    'regex:/[!@#$%^&*(),.?":{}|<>]/', // Must contain at least one special character
                ],
                // Validation messages for custom error messages
            ], [
                'password.required' => 'The password field is required.',
                'password.min' => 'The password must be at least 8 characters.',
                'password.confirmed' => 'The password confirmation does not match.',
                'password.regex' => 'The password must contain at least one number and one special character.',
                'username.required' => 'The username field is required.',
                'username.unique' => 'The username has already been taken.',
            ]);

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
                'code' => 201,
                'message' => 'User  registered successfully.',
                'user' => $user,
                'token' => $token
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation failed.',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while registering the user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'username_or_email' => 'required|string',
                'password' => 'required|string',
            ]);

            // Retrieve the login credentials
            $username_or_email = $request->input('username_or_email');
            $password = $request->input('password');

            // Find the user by email or username
            $user = User::where('email', $username_or_email)->orWhere('username', $username_or_email)->first();

            // Check if the user exists and the password is correct
            if (!$user || !Hash::check($password, $user->password)) {
                return response()->json([
                    'code' => 401,
                    'message' => 'The provided credentials are incorrect.'
                ], 401);
            }

            // Create a token for the user
            $token = $user->createToken('Token')->plainTextToken;

            return response()->json([
                'code' => 200,
                'message' => 'User logged in successfully.',
                'user' => $user,
                'token' => $token
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation failed.',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
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

            // Find the user associated with the token
            $user = User::whereHas('tokens', function ($query) use ($token) {
                $query->where('id', $token);
            })->first();

            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => 'Unauthorized. Token is invalid or user not found.'
                ], 401);
            }

            // Revoke all tokens for the user
            $user->tokens()->delete();

            return response()->json([
                'code' => 200,
                'message' => 'Logged out successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while logging out.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
