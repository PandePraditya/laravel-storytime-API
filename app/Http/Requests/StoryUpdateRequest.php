<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class StoryUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'content_images' => 'sometimes|array|max:3',
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
            'content_images.max' => 'You cannot upload more than 3 images.',
            'content_images.*.image' => 'File :attribute must be an image.',
            'content_images.*.mimes' => 'File :attribute must be a JPEG, PNG, JPG, or GIF.',
            'content_images.*.max' => 'File :attribute must not exceed 2MB.',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }
}
