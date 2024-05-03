<?php

namespace App\Http\Requests;

use App\Models\Subscriber;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Subscription;

class DepositBalanceRequest extends FormRequest
{
    public function rules(): array
    {
        $subscriber = Subscriber::where('user_id', $this->user()->id)
            ->where('meta_login', $this->to_meta_login)
            ->where('status', 'Subscribing')
            ->first();

        $amountRule = ['required', 'numeric', 'min:1'];

        // If subscription exists and amount must be in multiples of 100
        if ($subscriber) {
            $amountRule[] = 'integer';
            $amountRule[] = function ($attribute, $value, $fail) {
                if ($value % 100 !== 0) {
                    $fail(trans('public.amount_multiples_of_100'));
                }
            };
        }

        return [
            'wallet_id' => ['required'],
            'to_meta_login' => ['required'],
            'amount' => $amountRule,
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
