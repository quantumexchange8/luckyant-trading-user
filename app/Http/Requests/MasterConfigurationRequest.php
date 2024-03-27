<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterConfigurationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'min_join_equity' => ['required', 'numeric'],
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
            'min_join_equity' => trans('public.minimum_equity_to_join'),
            'signal_status' => trans('public.trade') . ' ' . trans('public.signal_status'),
        ];
    }
}
