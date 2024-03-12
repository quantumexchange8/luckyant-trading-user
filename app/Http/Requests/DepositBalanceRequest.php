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
            'wallet_id' => trans('public.sidebar.wallet'),
            'to_meta_login' => trans('public.account_number'),
            'amount' => trans('public.amount'),
        ];
    }
}
