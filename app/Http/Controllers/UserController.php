<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Log; // For checking logs
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // Get user details
    public function getUserDetails(Request $request)
    {
        try {
            $user = User::find($request->user()->id); // Get the authenticated user by ID

            if (!$user) {
                return response()->json(['message' => 'User  not found.'], 404);
            }

            return response()->json([
                'code' => 201,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_image' => $user->profile_image ? 'storage/' . $user->profile_image : null, // Return the profile image URL
                    'about' => $user->about,
                    'username' => $user->username,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'errors' => $e->getMessage(),
                'message' => 'An error occurred while fetching user details.',
            ], 500);
        }
    }

    // Update user profile and change password
    public function updateProfile(Request $request)
    {
        try {
            // Log::info($request->all());

            $request->validate([
                'name' => 'required|string|max:255',
                'about' => 'nullable|string|max:500',
                'old_password' => [
                    'nullable', // Allow null
                    'required_with:new_password', // Only required if new_password is present
                    'string',
                ],
                'new_password' => [
                    'nullable', // Allow null
                    'required_with:old_password', // Only required if old_password is present
                    'string',
                    'min:8', // Minimum length
                    'confirmed', // Must match the password confirmation
                    'different:old_password', // New password must be different from old password
                    'regex:/[0-9]/', // Must contain at least one number
                    'regex:/[!@#$%^&*(),.?":{}|<>]/', // Must contain at least one special character
                ],
            ], [
                'name.required' => 'The name field is required.',
                'about.max' => 'The about field must not exceed 500 characters.',
                'old_password.required_with' => 'The current password is required when changing password.',
                'new_password.required_with' => 'The new password is required.',
                'new_password.min' => 'The new password must be at least 8 characters long.',
                'new_password.confirmed' => 'The new password confirmation does not match.',
                'new_password.different' => 'The new password must be different from the current password.',
                'new_password.regex' => 'The new password must include number and special character.',
            ]);

            // Get the authenticated user by ID
            $user = User::find($request->user()->id);

            // Update profile fields
            $user->name = $request->name;
            $user->about = $request->about;

            // Check if the user wants to change the password
            if ($request->filled('old_password') && $request->filled('new_password')) {
                // Verify the old password
                if (!Hash::check($request->old_password, $user->password)) {
                    return response()->json([
                        'code' => 401,
                        'message' => 'The provided old password is incorrect.',
                    ], 401);
                }

                // Update the password
                $user->password = Hash::make($request->new_password);
            }

            $user->save(); // Save all changes

            return response()->json([
                'code' => 201,
                'message' => 'Profile updated successfully.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name, // will show the updated name
                    'about' => $user->about,
                ],
            ], 201);
        } catch (ValidationException $e) {
            // Return validation errors, like missing fields
            return response()->json(['errors' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            // Log the error
            return response()->json(['message' => 'An error occurred while updating the profile.', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateProfileImage(Request $request)
    {
        try {
            $request->validate([
                'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'profile_image.required' => 'Profile image is required.',
                'profile_image.image' => 'The profile image must be an image file.',
                'profile_image.mimes' => 'The profile image must be a JPEG, PNG, JPG, or GIF file.',
                'profile_image.max' => 'The profile image size must not exceed 2MB.',
            ]);

            // Get the authenticated user
            $user = User::find($request->user()->id);

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old profile image if exists
                if ($user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                // Store new profile image
                $imagePath = $request->file('profile_image')->store('profile_images', 'public');
                $user->profile_image = $imagePath;
                $user->save();

                return response()->json([
                    'code' => 201,
                    'message' => 'Profile image updated successfully.',
                    'user' => [
                        'id' => $user->id,
                        'profile_image' => asset('storage/' . $user->profile_image), // Return full URL
                    ],
                ], 201);
            }

            return response()->json([
                'code' => 400,
                'message' => 'No image uploaded.',
            ], 400);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the profile image.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
