<?php



namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContrFileRequest extends FormRequest
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
            'id'           => 'numeric',
            'contr'        => 'nullable|numeric',
            'store'        => 'nullable|numeric',
            'user'         => 'nullable|numeric',
            'type'         => 'nullable|string',
            'file'         => 'nullable|string',
            'format'       => 'nullable|string',
            'loaded'       => 'nullable|numeric',
            'count_insert' => 'nullable|numeric',
            'count_update' => 'nullable|numeric',
            'count_error'  => 'nullable|numeric',
        ];

    }
}
