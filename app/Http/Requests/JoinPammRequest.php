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
            'amount' => ['required'],
            'package_product' => ['required'],
            'terms' => ['accepted'],
        ];

        if ($master->delivery_requirement) {
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
        return [
            'amount' => trans('public.package'),
            'meta_login' => trans('public.account_no'),
            'package_product' => trans('public.select_product'),
            'delivery_address' => trans('public.delivery_address'),
            'terms' => trans('public.terms_and_conditions'),
        ];
    }
}
