<?php

namespace App\Http\Requests;

use App\Models\Master;
use Illuminate\Foundation\Http\FormRequest;

class JoinPammRequest extends FormRequest
{
    public function rules(): array
    {
        $master = Master::find($this->master_id);

        $rules = [
            'meta_login' => ['required'],
            'investment_amount' => ['required'],
            'terms' => ['accepted'],
        ];

        if ($master->delivery_requirement) {
            $rules['package_product'] = ['required'];
            $rules['delivery_address'] = ['required'];
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        $master = Master::find($this->master_id);

        return [
            'investment_amount' => $master->delivery_requirement ? trans('public.package') : trans('public.investment_amount'),
            'meta_login' => trans('public.account_no'),
            'package_product' => trans('public.select_product'),
            'delivery_address' => trans('public.delivery_address'),
            'terms' => trans('public.terms_and_conditions'),
        ];
    }
}
