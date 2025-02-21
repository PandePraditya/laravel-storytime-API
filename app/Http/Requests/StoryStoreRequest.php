<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class StoryStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is authenticated
        return auth('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'content_images' => 'sometimes|array|max:5',
            'content_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'required|exists:categories,id'
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'A title is required for your story.',
            'title.string' => 'The title must be text.',
            'title.max' => 'The title cannot exceed 255 characters.',
            
            'content.required' => 'Story content is required.',
            'content.string' => 'The content must be text.',
            
            'content_images.array' => 'Images must be provided as an array.',
            'content_images.max' => 'You cannot upload more than 5 images.',
            
            'content_images.*.image' => 'File :attribute must be an image.',
            'content_images.*.mimes' => 'File :attribute must be a JPEG, PNG, JPG, or GIF.',
            'content_images.*.max' => 'File :attribute must not exceed 2MB.',
            
            'category_id.required' => 'Please select a category for your story.',
            'category_id.exists' => 'The selected category does not exist.'
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
                'message' => 'Unauthorized. Please log in to create a story.'
            ], 401)
        );
    }
}
