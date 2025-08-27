<?php

namespace App\Http\Requests\Finance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FinanceInsertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        return true;

    }

    public function rules(): array
    {
        return [
            'id'       => ['required', 'numeric'],
            'cat_id'   => ['required', 'numeric'],
            'store_id' => ['nullable', 'numeric'],
            'date'     => ['required', 'date'],
            'sum'      => ['required', 'numeric'],
            'doc_num'  => ['nullable', 'string'],
            'doc_date' => ['nullable', 'date'],
            'doc_type' => ['nullable', 'string'],
            'title'    => ['required', 'string'],
            'text'     => ['nullable', 'string'],
            'items'    => ['nullable', 'array'],
        ];
    }

}
