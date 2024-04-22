<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:10'],
            'wallet_id' => ['required'],
            'wallet_address' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'amount' => trans('public.amount'),
            'wallet_id' => trans('public.sidebar.wallet'),
            'wallet_address' => trans('public.wallet_address'),
        ];
    }
}
