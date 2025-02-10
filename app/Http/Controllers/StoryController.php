<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoryStoreRequest;
use App\Http\Requests\StoryUpdateRequest;
use App\Models\Bookmark;
use App\Models\Story;
use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            /* 
            * Get authenticated user's ID or null if not authenticated
            * Will be use to check if the user has bookmarked the story 
            */
            $userId = auth('sanctum')->id() ?? null;

            $query = Story::with(['user', 'category'])
                ->select('id', 'title', 'content', 'content_images', 'user_id', 'category_id', 'created_at');

            // Search functionality & category filter
            $this->applyFilters($query, $request);

            // Sorting functionality with newest as default
            $this->applySorting($query, $request);
            // /api/stories?sort_by=popular&category=fiction&search=lorem

            // Check if pagination is requested
            $isPaginated = $request->has('per_page');

            // Get the stories
            if ($isPaginated) {
                $perPage = $request->input('per_page', 10);
                $stories = $query->paginate($perPage);
            } else {
                $stories = $query->get();
            }

            // Format the stories
            $formattedStories = $stories->map(function ($story) use ($userId) {
                // Get the user's name, or 'Unknown User' if no data
                $userName = $story->user ? $story->user->name : 'Unknown User';
                // Get the category name, uncategorized if no category
                $categoryName = $story->category ? $story->category->name : 'Uncategorized';

                // Get the user's profile picture if available, for showing the user's image of the story they made
                $userImage = $story->user && $story->user->profile_image
                    ? asset('storage/' . $story->user->profile_image)
                    : null;

                // Convert content_images to an array if it's a string
                $imagePaths = is_string($story->content_images)
                    ? json_decode($story->content_images)
                    : $story->content_images;

                // Map the image paths to a consistent format
                $content_images = array_map(function ($image, $key) {
                    return [
                        'id' => is_array($image) && isset($image['id']) ? $image['id'] : $key + 1,
                        'url' => is_array($image) && isset($image['url'])
                            ? $image['url']
                            : (is_string($image) ? $image : ''),
                    ];
                }, $imagePaths, array_keys($imagePaths));

                // Check if the story is bookmarked by the authenticated user
                $isBookmarked = $userId
                    ? Bookmark::where('story_id', $story->id)->where('user_id', $userId)->exists()
                    : false;

                return [
                    'id' => (string) $story->id,
                    'title' => $story->title,
                    'preview_content' => Str::words($story->content, 50), // Show only 50 words
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

            // Log the data
            Log::info('Story fetch successfully: ', [
                'stories' => $formattedStories,
            ]);

            /* 
            * Return the formatted stories
            * Include pagination meta if paginated
            */
            return response()->json([
                'data' => $formattedStories,
                'meta' => $isPaginated ? [
                    'current_page' => $stories->currentPage(),
                    'last_page' => $stories->lastPage(),
                    'per_page' => $stories->perPage(),
                    'total' => $stories->total()
                ] : null
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

    /** 
     * Apply Filters to the query based on the request
     * @param Builder $query as the query builder instance
     * @param Request $request as the request object
     * fn is shorthand for a closure function or arrow function, ex: instead of function($q) { ... } use
     */
    private function applyFilters(Builder $query, Request $request)
    {
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('content', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$searchTerm}%"))
                    ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$searchTerm}%"));
            });
        }

        if ($request->has('category')) {
            $query->whereHas('category', fn($q) => $q->where('name', $request->input('category')));
        }
    }

    /** 
     * Apply Sorting to the query based on the request
     * @param Builder $query as the query builder instance
     * @param Request $request as the request object
     * fn is shorthand for a closure function or arrow function, ex: instead of function($q) { ... } use
     */
    private function applySorting(Builder $query, Request $request)
    {
        // Default sort by creation date (newest first)
        $sortKey = $request->input('sort_by', 'newest');

        /** 
         * Sorting options:
         * newest: Sort by creation date (newest first)(default)
         * popular: Sort by bookmark count (highest first)
         * a-z: Sort alphabetically (A-Z)
         * z-a: Sort alphabetically (Z-A)
         */
        switch ($sortKey) {
            case 'popular':
                $query->withCount('bookmarks') // Count bookmarks for each item
                    ->orderBy('bookmarks_count', 'desc'); // Sort by bookmark count (highest first)
                break;
            case 'a-z':
                $query->orderBy('title', 'asc');
                break;
            case 'z-a':
                $query->orderBy('title', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
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

            // Log story creation
            Log::info('Story created successfully', [
                'story' => $story->toArray(),
            ]);

            // Return response
            return response()->json([
                'message' => 'Story created successfully',
                'data' => [
                    'id' => (string) $story->id, // Explicitly cast to string
                    ...$story->toArray()
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Story Store Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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

            $userName = $story->user ? $story->user->name : 'Unknown User';
            $profileImage = $story->user && $story->user->profile_image
                ? asset('storage/' . $story->user->profile_image)
                : null;

            // Ensure content_images is an array
            $imagePaths = is_string($story->content_images)
                ? json_decode($story->content_images, true) // Ensure associative array
                : $story->content_images;

            // Use collect() and map() for better handling of array data
            $content_images = collect($imagePaths)->map(function ($image, $key) {
                return [
                    'id' => is_array($image) && isset($image['id']) ? $image['id'] : $key + 1,
                    'url' => is_array($image) && isset($image['url']) ? $image['url'] : (is_string($image) ? $image : ''),
                ];
            })->all(); // Convert back to array

            return response()->json([
                'data' => [
                    'id' => (string) $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'content_images' => $content_images,
                    'user' => [
                        'name' => $userName,
                        'profile_image' => $profileImage
                    ],
                    'category' => [
                        'id' => $story->category_id,
                        'name' => $story->category->name ?? 'Uncategorized',
                    ],
                    'created_at' => $story->created_at ? $story->created_at->format('Y-m-d') : null,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Story Fetch Error: ', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Story not found or an error occurred',
                'error' => $e->getMessage()
            ], 404);
        }
    }


    public function update(StoryUpdateRequest $request, string $id)
    {
        try {
            // Get the authenticated user's ID
            $userId = auth('sanctum')->id();

            // Find the story by ID
            $story = Story::findOrFail($id);

            // Check if the authenticated user is the owner of the story
            if ($story->user_id !== $userId) {
                Log::warning('Unauthorized to update story with ID ' . $id);
                return response()->json([
                    'message' => 'Unauthorized to update this story'
                ], 403);
            }

            // Validate the request
            $validatedData = $request->validated();

            // Get existing images from the story
            $existingImages = $story->content_images ?? [];

            // Get the list of existing image IDs to keep
            $existingImageIds = $request->input('existing_images', []);
            if (is_string($existingImageIds)) {
                $existingImageIds = json_decode($existingImageIds, true) ?? [];
            }

            // Filter out removed images and keep only the ones that should remain
            $remainingImages = array_filter($existingImages, function ($image) use ($existingImageIds) {
                return in_array($image['id'], $existingImageIds);
            });

            // Handle new image uploads if any
            $newImages = [];
            if ($request->hasFile('content_images')) {
                foreach ($request->file('content_images') as $key => $image) {
                    $path = $image->store('story_images', 'public');
                    $newImages[] = [
                        'id' => count($remainingImages) + $key + 1, // Generate new sequential IDs
                        'url' => asset('storage/' . $path)
                    ];
                }
            }

            // Combine remaining existing images with new images
            $finalImages = array_merge($remainingImages, $newImages);

            // Update the story with the new data
            $story->update(array_merge($validatedData, [
                'content_images' => $finalImages,
            ]));

            // Return a success response
            return response()->json([
                'message' => 'Story updated successfully',
                'data' => [
                    'id' => (string) $story->id,
                    'title' => $story->title,
                    'content' => $story->content,
                    'content_images' => $story->content_images,
                    'category_id' => $story->category_id,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Story Update Error: ', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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

            // Ensure user is authenticated
            if (!$userId) {
                return response()->json([
                    'message' => 'Unauthorized, please login first.',
                ], 401);
            }

            // Find the story by ID or fail
            $story = Story::find($id); // Using find instead of findOrFail
            if (!$story) {
                return response()->json([
                    'message' => 'Story not found.',
                ], 404);
            }

            // Check if the authenticated user is the owner of the story
            if ($story->user_id !== $userId) {
                return response()->json([
                    'message' => 'Unauthorized to delete this story.',
                ], 403);
            }

            // Log the deletion, for debugging purposes
            Log::info('Deleting story with ID: ' . $id);

            // Delete associated images (if any)
            if (!empty($story->content_images)) {
                // Ensure content_images is an array (if it's stored as an array)
                $contentImages = is_array($story->content_images) ? $story->content_images : json_decode($story->content_images, true);

                foreach ($contentImages as $image) {
                    // Assuming image contains an 'url' field, which is the public URL
                    // Extract the relative path for storage deletion
                    $imagePath = str_replace(asset('storage/'), 'storage/', $image['url']);

                    // Delete image from storage if it exists
                    if (Storage::exists($imagePath)) {
                        Storage::delete($imagePath);
                    }
                }
            }

            // Delete the story
            $story->delete();

            // Return success response
            return response()->json([
                'message' => 'Story deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            // Catch any exception and log it for debugging
            Log::error('Error deleting story with ID ' . $id . ': ', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return a response with a 500 Internal Server Error status
            return response()->json([
                'message' => 'An error occurred while deleting the story.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Remove an image when editing the story, while not deleting the story
    // public function removeImage(Request $request, string $id)
    // {
    //     try {
    //         $validatedData = $request->validate([
    //             'image_id' => 'required|integer'
    //         ]);

    //         $story = Story::findOrFail($id);

    //         // Find the image to be removed
    //         $imageToRemove = collect($story->content_images)->firstWhere('id', $validatedData['image_id']);

    //         if (!$imageToRemove) {
    //             return response()->json([
    //                 'message' => 'Image not found'
    //             ], 404);
    //         }

    //         // Extract the relative path from the full URL
    //         $relativePath = str_replace(asset('storage/') . '/', '', $imageToRemove['url']);

    //         // Remove the image from storage
    //         if (Storage::disk('public')->exists($relativePath)) {
    //             Storage::disk('public')->delete($relativePath);
    //         }

    //         // Remove the image from the content_images array
    //         $updatedImages = array_values(array_filter($story->content_images, function ($image) use ($validatedData) {
    //             return $image['id'] !== $validatedData['image_id'];
    //         }));

    //         // Update the story with the remaining images
    //         $story->update([
    //             'content_images' => $updatedImages
    //         ]);

    //         return response()->json([
    //             'message' => 'Image removed successfully',
    //             'data' => $story
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Image Removal Error: ' . $e->getMessage());

    //         return response()->json([
    //             'message' => 'An error occurred while removing the image',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
