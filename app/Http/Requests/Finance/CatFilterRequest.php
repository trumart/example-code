<?php

namespace App\Http\Requests\Finance;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class CatFilterRequest extends FormRequest
{
    public function authorize(): bool
    {

        return true;

    }

    public function rules(): array
    {
        return [
            'date'  => ['required', 'date_format:Y-m'],
            'store' => ['required', 'numeric'],
        ];
    }

    public function passedValidation(): void
    {
        $month = Carbon::createFromFormat('Y-m', $this->input('date'));

        $this->merge([
            'date_start'  => $month->copy()->startOfMonth()->format('Y-m-d'),
            'date_finish' => $month->copy()->endOfMonth()->format('Y-m-d'),
        ]);
    }
}
