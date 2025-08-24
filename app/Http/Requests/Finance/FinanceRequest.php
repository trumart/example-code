<?php

namespace App\Http\Requests\Finance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FinanceRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {

        return [
            'id'            => ['numeric'],
            'cat_id'        => ['numeric'],
            'store_id'      => ['numeric'],
            'date'          => ['date'],
            'sum'           => ['numeric'],
            'company_title' => ['string'],
            'inn'           => ['numeric'],
            'doc_num'       => ['numeric'],
            'doc_date'      => ['date'],
            'doc_type'      => ['string'],
            'title'         => ['string'],
            'text'          => ['string'],
        ];

    }
}
