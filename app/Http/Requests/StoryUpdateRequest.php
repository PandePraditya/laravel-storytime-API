<?php

namespace App\Http\Requests;

use App\Models\Story;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

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
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'content_images' => 'sometimes|array',
            'content_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'sometimes|exists:categories,id',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Unauthorized. You are not authorized to update this story.'
            ], 403)
        );
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        Log::error('Story Validation Failed', [
            'errors' => $validator->errors(),
            'input' => $this->all()
        ]);

        throw new HttpResponseException(
            response()->json([
                'message' => 'Story validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}