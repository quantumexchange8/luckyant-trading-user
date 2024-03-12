<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
//            'front_identity' => ['required', 'image'],
//            'back_identity' => ['required', 'image'],
//            'verification_code' => ['required'],
            // 'terms' => ['accepted'],
            // 'market' => ['accepted'],
            // 'responsible' => ['accepted'],
            // 'compensate' => ['accepted'],
            'all' => ['accepted'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
//            'front_identity' => trans('public.Proof of Identity (FRONT)'),
//            'back_identity' => trans('public.Proof of Identity (BACK)'),
//            'verification_code' => trans('public.Verification Code'),
            // 'terms' => trans('public.Terms & Conditions'),
            // 'market' => trans('public.Terms & Conditions'),
            // 'responsible' => trans('public.Terms & Conditions'),
            // 'compensate' => trans('public.Terms & Conditions'),
            'all' => trans('public.terms_and_conditions'),
        ];
    }
}
