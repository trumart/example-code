<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class CatInsertRequest extends FormRequest
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
            'parent_id'  => ['required', 'numeric'],
            'title'      => ['required', 'string', 'unique:finance_cat,title', 'min:2', 'max:50'],
            'num'        => ['nullable'],
            'operating'  => ['nullable'],
            'nochange'   => ['nullable'],
            'noconsider' => ['nullable'],
            'document'   => ['nullable', 'boolean'],
        ];

    }
}
