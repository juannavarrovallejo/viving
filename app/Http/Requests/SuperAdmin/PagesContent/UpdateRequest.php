<?php

namespace App\Http\Requests\SuperAdmin\PagesContent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 'name' => 'required|string',
            // 'description' => 'nullable|string',
            // 'is_active' => 'nullable|in:1',
            // 'images' => 'nullable'
        ];
    }
}
