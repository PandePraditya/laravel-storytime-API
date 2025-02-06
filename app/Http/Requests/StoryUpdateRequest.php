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
        $storyId = $this->route('id');

        // Find the story but don't throw an exception if not found
        $story = Story::find($storyId);

        // If story doesn't exist, return false to trigger failedAuthorization
        if (!$story) {
            return false;
        }

        return auth('sanctum')->id() === $story->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'content_images' => 'sometimes|array|max:5',
            'content_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'sometimes|exists:categories,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.string' => 'The title must be text.',
            'title.max' => 'The title cannot exceed 255 characters.',
            'content.string' => 'The content must be text.',
            'content_images.array' => 'The images must be provided as an array.',
            'content_images.max' => 'You cannot upload more than 5 images.',
            'content_images.*.image' => 'File :attribute must be an image.',
            'content_images.*.mimes' => 'File :attribute must be a JPEG, PNG, JPG, or GIF.',
            'content_images.*.max' => 'File :attribute must not exceed 2MB.',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        Log::error('Validation Failed', [
            'errors' => $validator->errors(),
            'input' => $this->all()
        ]);
        // Return a JSON response with validation errors
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedAuthorization()
    {
        Log::error('Authorization Failed', [
            'user_id' => auth('sanctum')->id(),
            'route_id' => $this->route('id')
        ]);
        // Return a JSON response when the user is not authorized
        throw new HttpResponseException(
            response()->json([
                'message' => 'Unauthorized to update this story'
            ], 403)
        );
    }
}
