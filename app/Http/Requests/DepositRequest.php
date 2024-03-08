<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'wallet_id' => ['required'],
            'payment_method' => ['required'],
            'receipt' => $this->payment_method == 'Payment Merchant' ? ['nullable'] : ['required'],
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
            'payment_method' => 'Payment Methods',
            'wallet_id' => 'Wallet',
            'receipt' => 'Payment Slip',
            'amount' => 'Amount',
        ];
    }
}
