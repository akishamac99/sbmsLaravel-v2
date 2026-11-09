<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('staff') !== null;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'category_name' => ['required', 'string', 'max:100', Rule::unique('service_categories', 'category_name')->ignore($category?->getKey(), 'catID')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_event_type' => ['sometimes', 'boolean'],
        ];
    }
}
