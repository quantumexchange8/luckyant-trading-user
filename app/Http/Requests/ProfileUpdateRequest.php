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
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique(User::class)->ignore($this->user()->id)],
            'phone' => ['required', Rule::unique(User::class)->ignore($this->user()->id)],
            'dob' => ['required', 'date', 'date_format:Y-m-d'],
            'gender' => ['required'],
            'nationality' => ['required'],
            'identification_number' => ['required'],
            'address' => ['required'],
            'proof_front' => ['nullable', 'image', 'max:2048'],
            'proof_back' => ['nullable', 'image', 'max:2048'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
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
            'username' => trans('public.username'),
            'email' => trans('public.email'),
            'phone' => trans('public.mobile_phone'),
            'dob' => trans('public.date_of_birth'),
            'gender' => trans('public.gender'),
            'nationality' => trans('public.nationality'),
            'identification_number' => trans('public.identification_number'),
            'address' => trans('public.address'),
            'proof_front' => trans('public.proof_of_identity') . ' (' . trans('public.front') . ')',
            'proof_back' => trans('public.proof_of_identity') . ' (' . trans('public.back') . ')',
            'profile_photo' => trans('public.profile_photo'),
        ];
    }
}
