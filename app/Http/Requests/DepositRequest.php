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
            'amount' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'payment_method' => trans('public.payment_methods'),
            'wallet_id' => trans('public.sidebar.wallet'),
            'receipt' => trans('public.payment_slip'),
            'amount' => trans('public.amount'),
        ];
    }
}
