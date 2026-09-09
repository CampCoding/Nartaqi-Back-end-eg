<?php

namespace Modules\Store\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:books,bags,rounds,accessories',
            'hidden' => 'sometimes|boolean',
            'image' => 'required|string',
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
