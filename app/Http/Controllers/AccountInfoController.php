<?php

namespace App\Http\Controllers;

use AleeDhillon\MetaFive\Entities\User;
use AleeDhillon\MetaFive\Facades\Client;
use Inertia\Inertia;

class AccountInfoController extends Controller
{
    public function index()
    {
        return Inertia::render('AccountInfo/AccountInfo');
    }

    public function add_trading_account()
    {
        $user = new User();
        $user->setName("Test User 1");
        $user->setEmail("test1@example.com");
        $user->setGroup("JS");
        $user->setLeverage(500);
        $user->setPhone("0123456789");
        $user->setAddress("City View");
        $user->setCity("Malaysia");
        $user->setState("Kuala Lumpur");
        $user->setCountry("Kuala Lumpur");
        $user->setZipCode(54000);
        $user->setMainPassword("secret");
        $user->setInvestorPassword("secret");
        $user->setPhonePassword("secret");

        $result = Client::createUser($user);

        dd($result);
    }

}
