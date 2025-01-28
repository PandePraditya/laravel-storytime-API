<?php

namespace App\Http\Requests;

use App\Models\Story;
use Illuminate\Foundation\Http\FormRequest;

class StoryUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get the story ID from the route
        $storyId = $this->route('id');
        
        // Find the story
        $story = Story::findOrFail($storyId);
        
        // Check if the authenticated user is the owner of the story
        return auth('sanctum')->id() === $story->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
