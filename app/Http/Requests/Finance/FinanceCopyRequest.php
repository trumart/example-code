<?php

namespace App\Http\Requests\Finance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FinanceCopyRequest extends FormRequest
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
            'date'               => ['required', 'date'],
            'store_id'           => ['nullable', 'integer'],
            'store_cash_id'      => ['nullable', 'integer'],
            'title'              => ['required', 'string'],
            'text'               => ['nullable', 'string'],
            'sum'                => ['required', 'numeric'],
            'view'               => ['nullable', 'integer'],
            'check_store_accept' => ['nullable', 'boolean'],
            'nds'                => ['nullable', 'numeric'],
            'nds_val'            => ['nullable', 'numeric'],
            'type'               => ['required', 'string'],
            'cat_id'             => ['required', 'integer'],
            'cashbox'            => ['nullable', 'integer'],
            'paycash'            => ['nullable', 'integer'],
            'company_title'      => ['nullable', 'string'],
            'inn'                => ['nullable', 'numeric'],
            'doc_num'            => ['nullable', 'string'],
            'doc_date'           => ['nullable', 'date'],
            'doc_type'           => ['nullable', 'string'],
            'items'              => ['nullable', 'array'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'view'    => $this->view    ?? 1,
            'type'    => $this->type    ?? 'расход',
            'cashbox' => $this->cashbox ?? 2,
        ]);
    }
}
