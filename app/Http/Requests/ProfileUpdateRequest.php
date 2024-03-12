<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', Rule::unique(User::class)->ignore($this->user()->id)],
            'gender' => ['required'],
            'nationality' => ['required'],
            'identification_number' => ['required'],
            'address' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'name' => trans('public.name'),
            'phone' => trans('public.mobile_phone'),
            'gender' => trans('public.gender'),
            'nationality' => trans('public.nationality'),
            'identification_number' => trans('public.identification_number'),
            'address' => trans('public.address'),
        ];
    }
}
