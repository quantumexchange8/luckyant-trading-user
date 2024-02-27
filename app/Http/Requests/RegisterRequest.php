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
            'verification_code' => ['required'],
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
//            'front_identity' => trans('public.Proof of Identity (FRONT)'),
//            'back_identity' => trans('public.Proof of Identity (BACK)'),
            'verification_code' => trans('public.Verification Code'),
            'terms' => trans('public.Terms & Conditions'),
        ];
    }
}
