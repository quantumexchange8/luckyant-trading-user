<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawBalanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'to_wallet_id' => ['required'],
            'from_meta_login' => ['required'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'to_wallet_id' => 'Wallet',
            'from_meta_login' => 'Account Number',
            'amount' => 'Amount',
        ];
    }
}
