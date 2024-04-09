<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InternalTransferBalanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from_meta_login' => ['required'],
            'to_meta_login' => ['required'],
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
            'from_meta_login' => trans('public.transfer_from'),
            'to_meta_login' => trans('public.transfer_to'),
            'amount' => trans('public.amount'),
        ];
    }
}
