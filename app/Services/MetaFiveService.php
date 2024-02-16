<?php

namespace App\Services;

use App\Services\Data\CreateTradingAccount;
use App\Services\Data\CreateTradingUser;
use App\Services\Data\UpdateTradingAccount;
use App\Services\Data\UpdateTradingUser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User as UserModel;

class MetaFiveService {
    private string $port = "8443";
    private string $login = "10012";
    private string $password = "Test1234.";
//    private string $baseURL = "http://luckyant-mt5.currenttech.pro:5000/api";
    private string $baseURL = "http://192.168.0.223:5000/api";

    private string $token = "6f0d6f97-3042-4389-9655-9bc321f3fc1e";
    private string $environmentName = "live";

    public function getConnectionStatus()
    {
        try {
            return Http::acceptJson()->timeout(10)->get($this->baseURL . "/connect_status")->json();
        } catch (ConnectionException $exception) {
            // Handle the connection timeout error
            // For example, returning an empty array as a default response
            return [];
        }
    }

    public function getUser($meta_login)
    {
        return Http::acceptJson()->get($this->baseURL . "/trade_acc/{$meta_login}")->json();
    }

    public function getUserInfo($tradingAccounts): void
    {
        foreach ($tradingAccounts as $row) {
            $data = $this->getUser($row->meta_login);
            if($data) {
                (new UpdateTradingAccount)->execute($row->meta_login, $data);
                (new UpdateTradingUser)->execute($row->meta_login, $data);
            }
        }
    }

    public function createUser(UserModel $user, $group, $leverage)
    {
        $accountResponse = Http::acceptJson()->post($this->baseURL . "/create_user", [
            'name' => $user->name,
            'group' => $group,
            'leverage' => $leverage,
        ]);
        $accountResponse = $accountResponse->json();

        (new CreateTradingAccount)->execute($user, $accountResponse);
        (new CreateTradingUser)->execute($user, $accountResponse);
        return $accountResponse;
    }

    public function createDeal($meta_login, $amount, $comment, $type)
    {
        $dealResponse = Http::acceptJson()->post($this->baseURL . "/conduct_deal", [
            'login' => $meta_login,
            'amount' => $amount,
            'imtDeal_EnDealAction' => dealType::DEAL_BALANCE,
            'comment' => $comment,
            'deposit' => $type,
        ]);
        $dealResponse = $dealResponse->json();
        Log::debug($dealResponse);

        $data = $this->getUser($meta_login);
        (new UpdateTradingUser)->execute($meta_login, $data);
        (new UpdateTradingAccount)->execute($meta_login, $data);
        return $dealResponse;
    }
}

class dealAction
{
    const DEPOSIT = true;
    const WITHDRAW = false;
}

class dealType
{
    const DEAL_BALANCE = 2;
    const DEAL_CREDIT = 3;
    const DEAL_BONUS = 6;
}
