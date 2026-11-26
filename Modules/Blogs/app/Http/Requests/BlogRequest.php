<?php

namespace Modules\Blogs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Modules\Blogs\Models\Blog;

class BlogRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    /** The blog being edited (update_blog sends id), null when adding. */
    private function blogId(): ?int
    {
        $id = $this->input('id');
        return is_scalar($id) && ctype_digit((string) $id) ? (int) $id : null;
    }

    protected function prepareForValidation(): void
    {
        // "خطة مذاكرة القدرات" -> "خطة-مذاكرة-القدرات", so the unique check sees the final slug.
        if (is_string($this->input('slug')) && filled($this->input('slug'))) {
            $this->merge(['slug' => Blog::normalizeSlug($this->input('slug'), 120) ?: null]);
        }
    }

    /**
     * Every field is optional, when adding and when editing. Editing changes only the fields sent.
     */
    public function rules(): array
    {
        $id = $this->blogId();

        return [
            'title' => 'nullable|string|max:255',
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('blogs', 'slug')->ignore($id)],
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:300',
            // Checked only when a file is sent (the panel may send the current image link back as text).
            'image' => $this->hasFile('image') ? ['image', 'max:5120'] : ['nullable'],
            'image_alt' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
            'related_blogs_ids' => 'nullable',
            'hidden' => 'sometimes|boolean',
            'views' => 'sometimes|integer|min:0',
        ];
    }


    public function messages(): array
    {
        return [
            'title.max' => 'العنوان يجب ألا يزيد عن 255 حرفًا.',
            'slug.unique' => 'رابط المقال مستخدم في مقال آخر.',
            'slug.max' => 'رابط المقال يجب ألا يزيد عن 120 حرفًا.',
            'meta_title.max' => 'عنوان جوجل يجب ألا يزيد عن 70 حرفًا.',
            'meta_description.max' => 'وصف جوجل يجب ألا يزيد عن 300 حرف.',
            'image.image' => 'صورة الغلاف يجب أن تكون صورة.',
            'image.max' => 'حجم صورة الغلاف يجب ألا يزيد عن 5 ميجا.',
            'image_alt.max' => 'وصف الصورة يجب ألا يزيد عن 255 حرفًا.',
            'published_at.date' => 'يجب أن يكون تاريخًا صالحًا.',
            'hidden.boolean' => 'يجب أن يكون الحقل مخفيًا أو ظاهرًا.',
            'views.integer' => 'عدد المشاهدات يجب أن يكون رقمًا.',
            'views.min' => 'عدد المشاهدات لا يمكن أن يكون سالبًا.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }

    public function wantsJson(): bool
    {
        return true;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
