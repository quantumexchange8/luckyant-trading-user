<?php

namespace App\Services;

use App\Models\MasterSubscriptionPackage;
use App\Models\Wallet;
use App\Models\Country;
use App\Models\WalletLog;
use App\Models\Transaction;
use App\Models\PaymentAccount;
use App\Models\TradingAccount;
use App\Models\SettingLeverage;
use Str;

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
                'label' => $translations[$locale] ?? $country->name, //$translations['cn']
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

    public function getAllWallets(): \Illuminate\Support\Collection
    {
        return Wallet::where('user_id', \Auth::id())->get()->map(function ($wallet) {
            return [
                'value' => $wallet->id,
                'label' => trans('public.' . $wallet->type),
                'balance' => $wallet->balance,
            ];
        })->prepend(['value' => '', 'label' => trans('public.all')]);
    }

    public function getBonusType(): \Illuminate\Support\Collection
    {
        return WalletLog::distinct()->pluck('purpose')->map(function ($transactionType) {
            return [
                'value' => $transactionType,
                'label' => trans('public.' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $transactionType))),
            ];
        })->prepend(['value' => '', 'label' => trans('public.all')]);
    }

    public function getTradingAccounts(): \Illuminate\Support\Collection
    {
        return TradingAccount::where('user_id', \Auth::id())->get()->map(function ($tradingAccounts) {
            return [
                'value' => $tradingAccounts->meta_login,
                'label' => $tradingAccounts->meta_login,
            ];
        });
    }

    public function getTransactionType(): \Illuminate\Support\Collection
    {
        return Transaction::distinct()->whereNotNull('from_wallet_id')->orWhereNotNull('to_wallet_id')->pluck('transaction_type')->map(function ($transactionType) {
            return [
                'value' => $transactionType,
                'label' => trans('public.' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $transactionType))),
            ];
        })
        ->prepend(['value' => '', 'label' => trans('public.all')]);
    }

    public function getTradingAccountTransactionTypes(): \Illuminate\Support\Collection
    {
        return Transaction::distinct()
            ->where('category', 'trading_account')
            ->whereNot('transaction_type', 'Settlement')
            ->pluck('transaction_type')
            ->map(function ($transactionType) {
            return [
                'value' => $transactionType,
                'label' => trans('public.' . Str::snake($transactionType)),
            ];
        })
        ->prepend(['value' => '', 'label' => trans('public.all')]);
    }
}
