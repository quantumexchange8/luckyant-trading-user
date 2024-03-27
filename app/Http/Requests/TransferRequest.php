<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from_wallet' => ['required'],
            'wallet_address' => ['required', 'exists:wallets,wallet_address'],
            'amount' => ['required', 'numeric'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'from_wallet' => trans('public.transfer_from'),
            'wallet_address' => trans('public.wallet_address'),
            'amount' => trans('public.amount'),
        ];
    }
}
