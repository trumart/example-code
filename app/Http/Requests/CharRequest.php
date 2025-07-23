<?php



namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CharRequest extends FormRequest
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
            'num'        => 'nullable|numeric',
            'type'       => 'nullable|numeric',
            'title'      => 'nullable|string',
            'units'      => 'nullable|string',
            'val_min'    => 'nullable|numeric',
            'val_max'    => 'nullable|numeric',
            'display'    => 'nullable|string',
            'component'  => 'nullable|numeric',
            'site'       => 'nullable|numeric',
            'pricetag'   => 'nullable|numeric',
            'mandatory'  => 'nullable|numeric',
            'switch'     => 'nullable|numeric',
            'delimiter'  => 'nullable|string',
            'chars'      => 'nullable|string',
            'moder'      => 'nullable|numeric',
            'moder_user' => 'nullable|numeric',
        ];
    }
}
