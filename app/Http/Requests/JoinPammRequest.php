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
            'amount' => ['required'],
            'terms' => ['accepted'],
        ];

        if ($master->type == 'Standard') {
            $rules[] = [
                'meta_login' => ['required'],
            ];
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
            'amount' => trans('public.amount'),
            'meta_login' => trans('public.account_no'),
            'terms' => trans('public.terms_and_conditions'),
        ];
    }
}
