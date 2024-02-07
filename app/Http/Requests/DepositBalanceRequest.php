<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositBalanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'wallet_id' => ['required'],
            'to_meta_login' => ['required'],
            'amount' => ['required', 'numeric', 'min:30'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'wallet_id' => 'Wallet',
            'to_meta_login' => 'Account Number',
            'amount' => 'Amount',
        ];
    }
}
