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
        } catch(\Exception $e) {
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
            $bookmarks = $request->user()->bookmarks()->with('story.user', 'story.category')->get();
    
            $formattedBookmarks = $bookmarks->map(function ($bookmark) {
                $story = $bookmark->story;
                return [
                    'id' => (string) $story->id,
                    'title' => $story->title,
                    'preview_content' => Str::words($story->content, 50),
                    'first_image' => is_array($story->content_images) && !empty($story->content_images)
                        ? Storage::url($story->content_images[0])
                        : null,
                    'user' => $story->user ? $story->user->name : 'Unknown User',
                    'category' => $story->category ? $story->category->name : 'Uncategorized',
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
                'message' => 'An error occurred while fetching bookmark',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
