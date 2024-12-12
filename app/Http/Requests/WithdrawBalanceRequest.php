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
            'to_wallet_id' => trans('public.sidebar.wallet'),
            'from_meta_login' => trans('public.account_number'),
            'amount' => trans('public.amount'),
            'type' => trans('public.type'),
        ];
    }
}
