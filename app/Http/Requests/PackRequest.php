<?php



namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackRequest extends FormRequest
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
            'id'     => 'numeric',
            'status' => 'nullable|string',
            'title'  => 'nullable|string',
            'qr'     => 'nullable|string',
            'user'   => 'nullable|numeric',
            'row'    => 'nullable|string',
            'rack'   => 'nullable|string',
            'shelf'  => 'nullable|string',
            'cell'   => 'nullable|string',
        ];

    }
}
