<?php



namespace App\Http\Requests\Route;

use Illuminate\Foundation\Http\FormRequest;

class LoaderRequest extends FormRequest
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
            'id'             => 'nullable|numeric',
            'route_param_id' => 'nullable|numeric',
            'store_id'       => 'nullable|numeric',
            'price'          => 'nullable|numeric',
            'worker'         => 'nullable|numeric',
            'hour'           => 'nullable|numeric',
        ];

    }
}
