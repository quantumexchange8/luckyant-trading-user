<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'withdraw_wallets.*' => ['required', 'numeric', 'min:10'],
            'payment_account_id' => ['required'],
            'security_pin' => ['required'],
        ];
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
            'withdraw_wallets.*' => trans('public.amount'),
            'payment_account_id' => trans('public.wallet_address'),
            'security_pin' => trans('public.security_pin'),
        ];
    }
}
