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
        ];

        if ($this->payment_method == 'Bank') {
            $rules['bank_swift_code'] = ['required'];
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'payment_account_name' => $this->payment_method == 'Bank' ? 'Bank Account name' : 'Wallet address',
            'payment_platform_name' => $this->payment_method == 'Bank' ? 'Bank name' : 'Tether',
            'account_no' => $this->payment_method == 'Bank' ? 'Account Number' : 'Wallet Address',
            'bank_swift_code' => 'Bank Swift Code',
        ];
    }
}
