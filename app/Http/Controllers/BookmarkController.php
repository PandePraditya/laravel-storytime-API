<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookmarkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function toggle(Request $request)
    {
        try {
            if (!$request->user()) {
                return response()->json([
                    'message' => 'You need to log in to toggle a bookmark.',
                ], 401);
            }

            $request->validate([
                'story_id' => 'required|exists:stories,id',
            ]);

            $userId = $request->user()->id;
            $storyId = $request->input('story_id');

            // Check if the bookmark exists
            $bookmark = Bookmark::where('user_id', $userId)->where('story_id', $storyId)->first();

            if ($bookmark) {
                // Remove bookmark if it exists
                $bookmark->delete();
                return response()->json([
                    'message' => 'Bookmark removed successfully.',
                ]);
            } else {
                // Add bookmark if it does not exist
                Bookmark::create([
                    'user_id' => $userId,
                    'story_id' => $storyId,
                ]);
                return response()->json([
                    'message' => 'Story bookmarked successfully.',
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error('Bookmark Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all bookmarked stories for the authenticated user.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $bookmarks = $user->bookmarks()->with('story.user', 'story.category')->get();

            $formattedBookmarks = $bookmarks->map(function ($bookmark) use ($user) {
                $story = $bookmark->story;
                $userName = $story->user ? $story->user->name : 'Unknown User';
                $categoryName = $story->category ? $story->category->name : 'Uncategorized';

                // Get user's profile image
                $userImage = $story->user && $story->user->profile_image
                    ? asset('storage/' . $story->user->profile_image)
                    : null;

                // Handle content images as an array
                $imagePaths = is_string($story->content_images)
                    ? json_decode($story->content_images, true)
                    : $story->content_images;

                $content_images = array_map(function ($image, $key) {
                    return [
                        'id' => is_array($image) && isset($image['id']) ? $image['id'] : $key + 1,
                        'url' => is_array($image) && isset($image['url'])
                            ? $image['url']
                            : (is_string($image) ? $image : ''),
                    ];
                }, $imagePaths ?: [], array_keys($imagePaths ?: []));

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
                    'bookmarked' => true, // Since it's fetched from bookmarks, it's always true
                    'created_at' => $story->created_at ? $story->created_at->format('Y-m-d') : null,
                ];
            });

            return response()->json([
                'data' => $formattedBookmarks,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Bookmark Index Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching bookmarks',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
