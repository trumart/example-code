<?php



namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TypeRequest extends FormRequest
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
            'id'             => 'numeric',
            'page'           => 'nullable|numeric',
            'title'          => 'nullable|string',
            'title_short'    => 'nullable|string',
            'titles'         => 'nullable|string',
            'parent'         => 'nullable|numeric',
            'cat'            => 'nullable|numeric',
            'cat_distribute' => 'nullable|numeric',
            'select_hide'    => 'boolean',
            'cutting'        => 'boolean',
            'installation'   => 'boolean',
            'weight'         => 'nullable|numeric',
            'volume'         => 'nullable|numeric',
            'item_title'     => 'nullable|string',
            'sort'           => 'nullable|string',
        ];

    }
}
