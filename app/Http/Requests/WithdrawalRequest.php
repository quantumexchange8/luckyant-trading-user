<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    public function rules(): array
    {
        $withdrawal_fee = Setting::where('slug', 'withdrawal-fee')->first();
        return [
            'amount' => ['required', 'numeric', 'min:' . $withdrawal_fee->value],
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
            'amount' => trans('public.Amount'),
            'wallet_id' => 'Wallet',
            'wallet_address' => 'Wallet Address',
        ];
    }
}
