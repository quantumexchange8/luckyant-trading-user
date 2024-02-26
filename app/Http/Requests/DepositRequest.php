<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'wallet_id' => ['required'],
            'receipt' => ['required'],
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
            'receipt' => 'Payment Slip',
            'amount' => 'Amount',
        ];
    }
}
