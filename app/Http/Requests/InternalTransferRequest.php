<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InternalTransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from_wallet' => ['required'],
            'to_wallet' => ['required'],
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
            'to_wallet' => trans('public.transfer_to'),
            'amount' => trans('public.amount'),
        ];
    }
}
