<?php

namespace Modules\Store\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|exists:store,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|in:books,bags,rounds,accessories',
            'hidden' => 'sometimes|boolean',
            'image' => 'sometimes|string',
            'images' => 'sometimes|array',
            'images.*' => 'string',
            'book_urls' => 'sometimes|array',
            'book_urls.*' => 'string'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
