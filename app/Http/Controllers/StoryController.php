<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoryStoreRequest;
use App\Models\Bookmark;
use App\Models\Story;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            // $perPage = $request->input('per_page', 10);
            // $stories = $query->paginate($perPage);
            $stories = $query->get();

            $formattedStories = $stories->map(function ($story) use ($userId) {
                $userName = $story->user ? $story->user->name : 'Unknown User';
                $categoryName = $story->category ? $story->category->name : 'Uncategorized';

                // Get the user's profile picture if available, for showing the user's image of the story they made
                $userImage = $story->user && $story->user->profile_image
                    ? asset('storage/' . $story->user->profile_image)
                    : null;

                $imagePaths = is_string($story->content_images)
                    ? json_decode($story->content_images)
                    : $story->content_images;

                $content_images = array_map(function ($image, $key) {
                    return [
                        'id' => is_array($image) && isset($image['id']) ? $image['id'] : $key + 1,
                        'url' => is_array($image) && isset($image['url'])
                            ? $image['url']
                            : (is_string($image) ? $image : ''),
                    ];
                }, $imagePaths, array_keys($imagePaths));

                $isBookmarked = $userId
                    ? Bookmark::where('story_id', $story->id)->where('user_id', $userId)->exists()
                    : false;

                return [
                    'id' => (string) $story->id,
                    'title' => $story->title,
                    'preview_content' => Str::words($story->content, 50),
                    'content_images' => $content_images,
                    'user' => [
                        'name' => $userName,
                        'profile_image' => $userImage
                    ],
                    'category' => [
                        'id' => $story->category_id,
                        'name' => $categoryName,
                    ],
                    'bookmarked' => $isBookmarked, // Include bookmark status
                    'created_at' => $story->created_at ? $story->created_at->format('Y-m-d') : null,
                ];
            });

            return response()->json([
                'data' => $formattedStories,
                // 'meta' => [
                //     'current_page' => $stories->currentPage(),
                //     'last_page' => $stories->lastPage(),
                //     'per_page' => $stories->perPage(),
                //     'total' => $stories->total()
                // ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Story Index Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching stories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoryStoreRequest $request)
    {
        try {
            // Image upload logic
            $imagePaths = [];
            if ($request->hasFile('content_images')) {
                foreach ($request->file('content_images') as $key => $image) {
                    $path = $image->store('story_images', 'public');
                    $imagePaths[] = [
                        'id' => $key + 1,
                        'url' => asset('storage/' . $path)
                    ];
                }
            }

            // Create story
            $story = Story::create([
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'content_images' => $imagePaths, // Store as array
                'category_id' => $request->input('category_id'),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Story created successfully',
                'data' => [
                    'id' => (string) $story->id, // Explicitly cast to string
                    ...$story->toArray()
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Story Creation Error: ' . $e->getMessage());

            return response()->json([
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
            $imageData = collect($story->content_images)->map(function ($image) {
                return [
                    'id' => $image['id'],  // Keep the image ID
                    'url' => $image['url'] // Keep the image URL
                ];
            });

            return response()->json([
                'data' => [
                    'id' => (string) $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'content_images' => $imageData,
                    'user' => [
                        'name' => $story->user->name ?? 'Unknown User',
                        'profile_image' => $story->user->profile_image
                            ? asset('storage/' . $story->user->profile_image)
                            : null
                    ],
                    'category' => [
                        'id' => $story->category_id,
                        'name' => $story->category->name ?? 'Uncategorized',
                    ],
                    'created_at' => $story->created_at ? $story->created_at->format('Y-m-d') : null,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Story Fetch Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Story not found or an error occurred',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        // dd($request->file('content_images'));
        try {
            $userId = auth('sanctum')->id();

            // Find the story by ID
            $story = Story::findOrFail($id);

            if ($story->user_id !== $userId) {
                return response()->json([
                    'message' => 'Unauthorized to update this story'
                ], 403);
            }

            Log::info('Request Data: ', $request->all());

            // Validate request data
            $validatedData = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'content_images' => 'sometimes|array',
                'content_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // '*' means validate each item in the array
                'category_id' => 'sometimes|exists:categories,id'
            ]);

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

            // $this->file('content_images')->store('story_images', 'public');

            // Update the story with validated data
            $story->update(array_merge($validatedData, [
                'content_images' => $imagePaths, // Store the updated array of images
            ]));

            // Return success response
            return response()->json([
                'message' => 'Story updated successfully',
                'data' => [
                    'id' => (string) $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'content_images' => collect($story->content_images)
                        ->map(function ($imagePath) {
                            return Storage::url($imagePath);
                        }),
                    'category_id' => $story->category_id,
                ]
            ], 200);
        } catch (\Exception $e) {
            // Log error and return response
            Log::error('Story Update Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while updating the story',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy(string $id)
    {
        try {
            // Get the authenticated user's ID
            $userId = auth('sanctum')->id();

            // Find the story by ID or fail
            $story = Story::findOrFail($id);

            // Check if the authenticated user is the owner of the story
            if ($story->user_id !== $userId) {
                return response()->json([
                    'message' => 'Unauthorized to delete this story'
                ], 403);
            }

            // Log the deletion, for debugging purposes
            Log::info('Deleting story with ID: ' . $id);

            // Delete associated images
            if (!empty($story->content_images)) {
                $contentImages = is_string($story->content_images)
                    ? json_decode($story->content_images)
                    : $story->content_images;

                foreach ($contentImages as $imagePath) {
                    if (Storage::exists($imagePath)) {
                        Storage::delete($imagePath);
                    }
                }
            }

            // Delete the story
            $story->delete();

            return response()->json([
                'message' => 'Story deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Story Not Found: ' . $e->getMessage());

            return response()->json([
                'message' => 'Story not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Story Deletion Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while deleting the story',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Remove an image when editing the story, while not deleting the story
    public function removeImage(Request $request, string $id)
    {
        try {
            $validatedData = $request->validate([
                'image_id' => 'required|integer'
            ]);

            $story = Story::findOrFail($id);

            // Find the image to be removed
            $imageToRemove = collect($story->content_images)->firstWhere('id', $validatedData['image_id']);

            if (!$imageToRemove) {
                return response()->json([
                    'message' => 'Image not found'
                ], 404);
            }

            // Extract the relative path from the full URL
            $relativePath = str_replace(asset('storage/') . '/', '', $imageToRemove['url']);

            // Remove the image from storage
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }

            // Remove the image from the content_images array
            $updatedImages = array_values(array_filter($story->content_images, function ($image) use ($validatedData) {
                return $image['id'] !== $validatedData['image_id'];
            }));

            // Update the story with the remaining images
            $story->update([
                'content_images' => $updatedImages
            ]);

            return response()->json([
                'message' => 'Image removed successfully',
                'data' => $story
            ], 200);
        } catch (\Exception $e) {
            Log::error('Image Removal Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while removing the image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
