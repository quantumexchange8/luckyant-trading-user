<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Wallet;
use App\Models\PaymentAccount;
use App\Models\SettingLeverage;

class SelectOptionService
{
    public function getWalletSelection(): \Illuminate\Support\Collection
    {
        $wallets = Wallet::where('user_id', \Auth::id())->whereIn('type', ['cash_wallet', 'e_wallet']);

        return $wallets->get()->map(function ($wallet) {
            return [
                'value' => $wallet->id,
                'name' => trans('public.' . $wallet->type),
                'label' => trans('public.' . $wallet->type) . ' ($' . number_format($wallet->balance, 2) . ')',
                'balance' => $wallet->balance,
            ];
        });
    }

    public function getInternalTransferWalletSelection(): \Illuminate\Support\Collection
    {
        $wallets = Wallet::where('user_id', \Auth::id())->where('type', 'e_wallet');

        return $wallets->get()->map(function ($wallet) {
            return [
                'value' => $wallet->id,
                'label' => trans('public.' . $wallet->type) . ' ($' . number_format($wallet->balance, 2) . ')',
                'balance' => $wallet->balance,
            ];
        });
    }

    public function getPaymentAccountSelection(): \Illuminate\Support\Collection
    {
        $paymentAccounts = PaymentAccount::where('user_id', \Auth::id())->where('status', 'Active')->latest();

        return $paymentAccounts->get()->map(function ($payment_account) {
            return [
                'value' => $payment_account->id,
                'label' => $payment_account->payment_platform_name . ' (' . $payment_account->payment_account_name . ')',
            ];
        });
    }

    public function getActiveLeverageSelection(): \Illuminate\Support\Collection
    {
        $settingLeverages = SettingLeverage::where('status', 'Active');

        return $settingLeverages->get()->map(function ($settingLeverage) {
            return [
                'label' => $settingLeverage->display,
                'value' => $settingLeverage->value,
            ];
        });
    }

    public function getCountries()
    {
        $locale = app()->getLocale();

        return Country::all()->map(function ($country) use ($locale) {
            $translations = json_decode($country->translations, true);

            return [
                'value' => $country->id,
                'label' => $translations[$locale] ?? $country->name,
            ];
        });
    }

    public function getNationalities()
    {
        return Country::all()->map(function ($country) {
            return [
                'id' => $country->id,
                'value' => $country->nationality,
                'label' => $country->nationality,
            ];
        });
    }

    public function getCurrencies(): \Illuminate\Support\Collection
    {
        return Country::whereIn('id', [132, 233])->get()->map(function ($country) {
            return [
                'value' => $country->currency,
                'label' => $country->currency_name . ' (' . $country->currency . ')',
            ];
        });
    }

}
