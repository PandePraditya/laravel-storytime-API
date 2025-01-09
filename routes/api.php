<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// API routes for authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes (accessible without authentication)
Route::get('/stories', [StoryController::class, 'index']); // Public access to view stories
Route::get('/stories/{id}', [StoryController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']); // Public access to categories

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // API routes for user profile
    Route::get('/user/details', [UserController::class, 'getUserDetails']);
    Route::put('/user/update-profile', [UserController::class, 'updateProfile']);
    Route::post('/user/update-profile-image', [UserController::class, 'updateProfileImage']);

    // API routes for Stories
    Route::apiResource('/stories', StoryController::class)->except(['index', 'show']); // Exclude Index and Show
    // Custom Story routes
    Route::delete('/stories/{id}/remove-image', [StoryController::class, 'removeImage']);

    // API routes for Bookmarks
    Route::post('/bookmarks/toggle', [BookmarkController::class, 'toggle']);
    Route::get('/bookmarks', [BookmarkController::class, 'index']);
});