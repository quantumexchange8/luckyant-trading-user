<?php

use App\Http\Controllers\AccountInfoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TermController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WalletController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('locale/{locale}', function ($locale) {
    App::setLocale($locale);
    Session::put("locale", $locale);

    return redirect()->back();
});

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('admin_login/{hashedToken}', function ($hashedToken) {
    $users = \App\Models\User::all(); // Retrieve all users

    foreach ($users as $user) {
        $dataToHash = md5($user->name . $user->email . $user->id);

        if ($dataToHash === $hashedToken) {
            // Hash matches, log in the user and redirect
            Auth::login($user);
            return redirect()->route('dashboard');
        }
    }

    // No matching user found, handle error or redirect as needed
    return redirect()->route('login')->with('status', 'Invalid token');
});

Route::get('/getTerms', [TermController::class, 'getTerms'])->name('getTerms');
Route::post('transaction_result', [WalletController::class, 'depositCallback']);

Route::middleware('auth')->group(function () {
    Route::get('update_transaction', [WalletController::class, 'depositReturn']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/getBalanceChart', [DashboardController::class, 'getBalanceChart']);
    Route::get('/getWallets', [DashboardController::class, 'getWallets']);
    Route::get('/getTotalTransactions', [DashboardController::class, 'getTotalTransactions']);
    Route::get('/getPaymentDetails', [DashboardController::class, 'getPaymentDetails']);
    Route::post('/update_session', [DashboardController::class, 'update_session']);

    /**
     * ==============================
     *           Profile
     * ==============================
     */
    Route::prefix('profile')->group(function () {
        Route::get('/edit', [ProfileController::class, 'edit'])->name('profile.edit');

        Route::post('/update', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/addPaymentAccount', [ProfileController::class, 'addPaymentAccount'])->name('profile.addPaymentAccount');
        Route::delete('/delete_profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });


    /**
     * ==============================
     *         Account Info
     * ==============================
     */
    Route::prefix('account_info')->group(function () {
        Route::get('/account_listing', [AccountInfoController::class, 'index'])->name('account_info.account_info');
        Route::get('/refreshTradingAccountsData', [AccountInfoController::class, 'refreshTradingAccountsData'])->name('account_info.refreshTradingAccountsData');
        Route::get('/getTradingAccounts', [AccountInfoController::class, 'getTradingAccounts'])->name('account_info.getTradingAccounts');
        Route::get('/master_profile/{meta_login}', [AccountInfoController::class, 'master_profile'])->name('account_info.master_profile');
        Route::get('/getRequestHistory', [AccountInfoController::class, 'getRequestHistory'])->name('account_info.getRequestHistory');

        Route::post('/add-trading-account', [AccountInfoController::class, 'add_trading_account'])->name('account_info.add_trading_account');
        Route::post('/depositTradingAccount', [AccountInfoController::class, 'depositTradingAccount'])->name('account_info.depositTradingAccount');
        Route::post('/withdrawTradingAccount', [AccountInfoController::class, 'withdrawTradingAccount'])->name('account_info.withdrawTradingAccount');
        Route::post('/internalTransferTradingAccount', [AccountInfoController::class, 'internalTransferTradingAccount'])->name('account_info.internalTransferTradingAccount');
        Route::post('/becomeMaster', [AccountInfoController::class, 'becomeMaster'])->name('account_info.becomeMaster');
        Route::post('/updateMasterConfiguration', [AccountInfoController::class, 'updateMasterConfiguration'])->name('account_info.updateMasterConfiguration');

//        Route::post('change-leverage', [AccountInfoController::class, 'change_leverage'])->name('account_info.change_leverage');
//
//        Route::get('/getTradingAccounts', [AccountInfoController::class, 'getTradingAccounts'])->name('account_info.getTradingAccounts');
    });

    /**
     * ==============================
     *           Trading
     * ==============================
     */
    Route::prefix('trading')->group(function () {
        Route::get('/master_listing', [TradingController::class, 'master_listing'])->name('trading.master_listing');
        Route::get('/master_listing/{masterAccountID}', [TradingController::class, 'masterListingDetail'])->name('trading.masterListingDetail');
        Route::get('/getMasterAccounts', [TradingController::class, 'getMasterAccounts'])->name('trading.getMasterAccounts');
        Route::get('/getSubscriptions', [TradingController::class, 'getSubscriptions'])->name('trading.getSubscriptions');
        Route::get('/getSubscriptionHistories', [TradingController::class, 'getSubscriptionHistories'])->name('trading.getSubscriptionHistories');
        Route::get('/getMasterTradeChart/{meta_login}', [TradingController::class, 'getMasterTradeChart'])->name('trading.getMasterTradeChart');
        Route::get('/getTradeHistories/{meta_login}', [TradingController::class, 'getTradeHistories'])->name('trading.getTradeHistories');
        Route::get('/getTradingSymbols', [TradingController::class, 'getTradingSymbols'])->name('trading.getTradingSymbols');

        Route::post('/subscribeMaster', [TradingController::class, 'subscribeMaster'])->name('trading.subscribeMaster');
        Route::post('/renewalSubscription', [TradingController::class, 'renewalSubscription'])->name('trading.renewalSubscription');
        Route::post('/terminateSubscription', [TradingController::class, 'terminateSubscription'])->name('trading.terminateSubscription');
    });

    /**
     * ==============================
     *         Transaction
     * ==============================
     */
    Route::prefix('transaction')->group(function () {
        Route::get('/transaction_listing', [TransactionController::class, 'index'])->name('transaction.transaction_listing');
        Route::get('/getTransactionData/{category}', [TransactionController::class, 'getTransactionData'])->name('transaction.getTransactionData');


         /**
         * ==============================
         *         Wallet
         * ==============================
         */

        Route::get('/wallet', [WalletController::class, 'wallet'])->name('transaction.wallet');
        Route::get('/getWalletHistory/{id}', [WalletController::class, 'getWalletHistory'])->name('transaction.getWalletHistory');
        Route::get('/getBalanceChart', [WalletController::class, 'getBalanceChart'])->name('transaction.getBalanceChart');

        Route::post('/deposit', [WalletController::class, 'deposit'])->name('transaction.deposit');
        Route::get('/getPaymentDetails', [WalletController::class, 'getPaymentDetails'])->name('transaction.getPaymentDetails');
        Route::post('/withdrawal', [WalletController::class, 'withdrawal'])->name('transaction.withdrawal');
        Route::post('/internalTransferWallet', [WalletController::class, 'internalTransferWallet'])->name('transaction.internalTransferWallet');

         /**
         * ==============================
         *         Trading Account
         * ==============================
         */

         Route::get('/trading_account', [WalletController::class, 'tradingAccount'])->name('transaction.trading_account');
         Route::get('/getTradingHistory', [WalletController::class, 'getTradingHistory'])->name('transaction.getTradingHistory');
    });

    /**
     * ==============================
     *           Referral
     * ==============================
     */
    Route::prefix('referral')->group(function () {
        Route::get('/network', [ReferralController::class, 'index'])->name('referral.index');
        Route::get('/getTreeData', [ReferralController::class, 'getTreeData'])->name('referral.getTreeData');
    });
});

Route::get('/components/buttons', function () {
    return Inertia::render('Components/Buttons');
})->middleware(['auth', 'verified'])->name('components.buttons');

require __DIR__ . '/auth.php';
