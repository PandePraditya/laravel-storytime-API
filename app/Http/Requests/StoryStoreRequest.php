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
            'content_images' => 'sometimes|array',
            'content_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'required|exists:categories,id'
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
                'message' => 'Story Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
