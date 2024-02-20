<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterConfigurationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'min_join_equity' => ['required', 'numeric'],
            'sharing_profit' => ['required', 'numeric'],
            'subscription_fee' => ['required', 'numeric'],
            'signal_status' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'min_join_equity' => 'Minimum Equity',
            'sharing_profit' => 'Sharing Profit (%)',
            'subscription_fee' => 'Subscription Fee (Month)',
            'signal_status' => 'Trade Signal Status',
        ];
    }
}
