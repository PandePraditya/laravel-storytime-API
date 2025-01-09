<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Story;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $userId = auth('sanctum')->id(); // Get authenticated user's ID or null if not authenticated

            $query = Story::with(['user', 'category'])
                ->select('id', 'title', 'content', 'content_images', 'user_id', 'category_id', 'created_at');

            // Search functionality
            if ($request->has('search')) {
                $searchTerm = $request->input('search');
                $query->where(function (Builder $q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                        ->orWhere('content', 'like', "%{$searchTerm}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($searchTerm) {
                            $userQuery->where('name', 'like', "%{$searchTerm}%");
                        })
                        ->orWhereHas('category', function (Builder $categoryQuery) use ($searchTerm) {
                            $categoryQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                });
            }

            // Filtering by category
            if ($request->has('category')) {
                $query->whereHas('category', function (Builder $q) use ($request) {
                    $q->where('name', $request->input('category'));
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->input('per_page', 10);
            $stories = $query->paginate($perPage);

            $formattedStories = $stories->map(function ($story) use ($userId) {
                $userName = $story->user ? $story->user->name : 'Unknown User';
                $categoryName = $story->category ? $story->category->name : 'Uncategorized';

                $firstImage = is_array($story->content_images) && !empty($story->content_images)
                    ? Storage::url($story->content_images[0])
                    : null;

                $isBookmarked = $userId
                    ? Bookmark::where('story_id', $story->id)->where('user_id', $userId)->exists()
                    : false;

                return [
                    'id' => (string) $story->id,
                    'title' => $story->title,
                    'preview_content' => Str::words($story->content, 50),
                    'first_image' => $firstImage,
                    'user' => $userName,
                    'category' => $categoryName,
                    'bookmarked' => $isBookmarked, // Include bookmark status
                    'created_at' => $story->created_at ? $story->created_at->format('Y-m-d') : null,
                ];
            });

            return response()->json([
                'code' => 200,
                'data' => $formattedStories,
                'meta' => [
                    'current_page' => $stories->currentPage(),
                    'last_page' => $stories->lastPage(),
                    'per_page' => $stories->perPage(),
                    'total' => $stories->total()
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Story Index Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while fetching stories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'content_images' => 'sometimes|array',
                'content_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                'category_id' => 'required|exists:categories,id'
            ]);

            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('content_images')) {
                foreach ($request->file('content_images') as $image) {
                    $path = $image->store('story_images', 'public');
                    $imagePaths[] = $path;
                }
            }

            $story = Story::create([
                'title' => $validatedData['title'],
                'content' => $validatedData['content'],
                'content_images' => $imagePaths, // Store as array
                'category_id' => $validatedData['category_id'],
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'code' => 201,
                'message' => 'Story created successfully',
                'data' => [
                    'id' => (string) $story->id, // Explicitly cast to string
                    ...$story->toArray()
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Story Creation Error: ' . $e->getMessage());

            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while creating the story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $story = Story::with(['user', 'category'])->findOrFail($id);

            // Transform image paths to full URLs
            $imageUrls = collect($story->content_images)
                ->map(function ($imagePath) {
                    return Storage::url($imagePath);
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => (string) $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'content_images' => $imageUrls,
                    'user' => $story->user->name ?? 'Unknown User',
                    'category' => $story->category->name ?? 'Uncategorized'
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Story Fetch Error: ' . $e->getMessage());

            return response()->json([
                'code' => 404,
                'message' => 'Story not found or an error occurred',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            Log::info('Request Data: ', $request->all());

            // Validate request data
            $validatedData = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'content_images' => 'sometimes|array',
                'content_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                'category_id' => 'sometimes|exists:categories,id'
            ]);

            // Find the story by ID
            $story = Story::findOrFail($id);

            // Initialize image paths with existing images
            $imagePaths = $story->content_images;

            // Handle image uploads if new images are provided
            if ($request->hasFile('content_images')) {
                // Clear existing images if you want to replace them
                $imagePaths = []; // Replace with an empty array

                foreach ($request->file('content_images') as $image) {
                    $path = $image->store('story_images', 'public');
                    $imagePaths[] = $path; // Append new images
                }
            }

            // Update the story with validated data
            $story->update(array_merge($validatedData, [
                'content_images' => $imagePaths, // Store the updated array of images
            ]));

            // Return success response
            return response()->json([
                'code' => 200,
                'message' => 'Story updated successfully',
                'data' => [
                    'id' => (string)$story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'content_images' => $story->content_images,
                    'category_id' => $story->category_id,
                ]
            ], 200);
        } catch (\Exception $e) {
            // Log error and return response
            Log::error('Story Update Error: ' . $e->getMessage());

            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while updating the story',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy(string $id)
    {
        try {
            $story = Story::findOrFail($id);

            Log::info('Deleting story with ID: ' . $id);
            // Delete associated images
            if (!empty($story->content_images)) {
                foreach ($story->content_images as $imagePath) {
                    if (Storage::exists($imagePath)) {
                        Storage::delete($imagePath);
                    }
                }
            }

            $story->delete();

            return response()->json([
                'success' => true,
                'message' => 'Story deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Story Deletion Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function removeImage(Request $request, string $id)
    {
        try {
            $validatedData = $request->validate([
                'image_path' => 'required|string'
            ]);

            $story = Story::findOrFail($id);

            // Filter out the image to be removed
            $updatedImages = array_filter($story->content_images ?? [], function ($image) use ($validatedData) {
                return $image !== $validatedData['image_path'];
            });

            // Delete the image from storage
            if (Storage::exists($validatedData['image_path'])) {
                Storage::delete($validatedData['image_path']);
            }

            // Update the story with the remaining images
            $story->update([
                'content_images' => array_values($updatedImages)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image removed successfully',
                'data' => $story
            ], 200);
        } catch (\Exception $e) {
            Log::error('Image Removal Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
