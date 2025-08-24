<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        return true;

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'id'        => 'nullable|numeric',
            'inn'       => 'nullable|numeric',
            'cat'       => 'nullable|numeric',
            'text'      => 'nullable|string',
            'unloading' => 'nullable|boolean',
            'nds'       => 'nullable|boolean',
            'nds_val'   => 'nullable|numeric',
        ];

    }
}
