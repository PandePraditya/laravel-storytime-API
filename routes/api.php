<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
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
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // API routes for user profile
    Route::get('/user/details', [UserController::class, 'getUserDetails']);
    Route::put('/user/update-profile', [UserController::class, 'updateProfile']);
    Route::post('/user/update-profile-image', [UserController::class, 'updateProfileImage']);

    // API routes for Stories
    Route::apiResource('/stories', StoryController::class);
    Route::delete('/stories/{id}/remove-image', [StoryController::class, 'removeImage']);
});