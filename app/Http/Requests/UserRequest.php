<?php



namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'id'         => 'numeric',
            'mail'       => 'nullable|string',
            'password'   => 'nullable|string',
            'post'       => 'nullable|string',
            'store'      => 'nullable|numeric',
            'department' => 'nullable|string',
            'bet'        => 'nullable|numeric',
            'mode'       => 'nullable|string',
            'percent'    => 'nullable|numeric',
            'salary'     => 'nullable|numeric',
        ];

    }
}
