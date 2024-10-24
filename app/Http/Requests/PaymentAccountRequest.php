<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentAccountRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'payment_account_name' => ['required'],
            'payment_platform_name' => ['required'],
            'account_no' => ['required'],
            'security_pin' => ['sometimes', 'required'],
        ];

        if ($this->payment_method == 'Bank') {
            $rules['bank_swift_code'] = ['nullable'];
            $rules['bank_sub_branch'] = ['required'];
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator): void
    {
        // checks user current password
        // before making changes
        $validator->after(function ($validator) {
            if ($this->security_pin && !\Hash::check($this->security_pin, $this->user()->security_pin) ) {
                $validator->errors()->add('security_pin', trans('public.current_pin_invalid'));
            }
        });
        return;
    }

    public function attributes(): array
    {
        return [
            'payment_account_name' => trans($this->payment_method == 'Bank' ? 'public.bank_account_name' : 'public.crypto_wallet_name'),
            'payment_platform_name' => trans($this->payment_method == 'Bank' ? 'public.bank_name' : 'public.payment_service'),
            'account_no' => trans($this->payment_method == 'Bank' ? 'public.account_number' : 'public.wallet_address'),
            'bank_swift_code' => trans('public.bank_swift_code'),
            'bank_sub_branch' => trans('public.bank_sub_branch'),
            'security_pin' => trans('public.security_pin'),
        ];
    }
}
