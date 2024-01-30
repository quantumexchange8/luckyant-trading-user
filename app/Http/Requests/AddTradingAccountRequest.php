<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTradingAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'leverage' => ['required'],
            'terms' => ['accepted']
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'leverage' => trans('public.Leverage'),
            'terms' => trans('public.Terms & Conditions'),
        ];
    }
}
