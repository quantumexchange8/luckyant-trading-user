<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CopyTradingController;
use App\Http\Controllers\PammController;
use App\Http\Controllers\SelectOptionController;
use Inertia\Inertia;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\TermController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountInfoController;
use App\Http\Controllers\TransactionController;

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
Route::post('tt_pay_callback', [WalletController::class, 'tt_pay_callback']);

Route::middleware('auth')->group(function () {
    // Select Options
    Route::get('getAccountTypes', [SelectOptionController::class, 'getAccountTypes']);
    Route::get('getLeverages', [SelectOptionController::class, 'getLeverages']);
    Route::get('getDepositWallets', [SelectOptionController::class, 'getDepositWallets']);

    Route::get('update_transaction', [WalletController::class, 'depositReturn']);
    Route::get('tt_pay_return', [WalletController::class, 'tt_pay_return']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/getBalanceChart', [DashboardController::class, 'getBalanceChart']);
    Route::get('/getWallets', [DashboardController::class, 'getWallets']);
    Route::get('/getTotalTransactions', [DashboardController::class, 'getTotalTransactions']);
    Route::get('/getPaymentDetails', [DashboardController::class, 'getPaymentDetails']);
    Route::get('/getDepositOptions', [DashboardController::class, 'getDepositOptions']);

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
        Route::post('/editPaymentAccount', [ProfileController::class, 'editPaymentAccount'])->name('profile.editPaymentAccount');
        Route::post('/deletePaymentAccount', [ProfileController::class, 'deletePaymentAccount'])->name('profile.deletePaymentAccount');
        Route::post('/sendOtp', [ProfileController::class, 'sendOtp'])->name('profile.sendOtp');
        Route::delete('/delete_profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });


    /**
     * ==============================
     *         Account Info
     * ==============================
     */
    Route::prefix('account_info')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('account_info');
        Route::get('/getTradingAccountsData', [AccountController::class, 'getTradingAccountsData'])->name('account_info.getTradingAccountsData');
        Route::get('/getAccountReport', [AccountController::class, 'getAccountReport'])->name('account_info.getAccountReport');

        Route::post('/createAccount', [AccountController::class, 'createAccount'])->name('account_info.createAccount');
        Route::post('/depositBalance', [AccountController::class, 'depositBalance'])->name('account_info.depositBalance');
//        Route::get('/account_listing', [AccountInfoController::class, 'index'])->name('account_info.account_info');

        // after change get master delete
        Route::get('/refreshTradingAccountsData', [AccountInfoController::class, 'refreshTradingAccountsData'])->name('account_info.refreshTradingAccountsData');
        Route::get('/getTradingAccounts', [AccountInfoController::class, 'getTradingAccounts'])->name('account_info.getTradingAccounts');
        // end

        Route::get('/master_profile/{meta_login}', [AccountInfoController::class, 'master_profile'])->name('account_info.master_profile');
        Route::get('/getRequestHistory', [AccountInfoController::class, 'getRequestHistory'])->name('account_info.getRequestHistory');
//        Route::get('/transaction_history', [TransactionController::class, 'index'])->name('account_info.transaction_listing');
//        Route::get('/getTransactionData', [TransactionController::class, 'getTransactionData'])->name('account_info.getTransactionData');
        Route::get('/getInactiveAccountsData', [AccountInfoController::class, 'getInactiveAccountsData'])->name('account_info.getInactiveAccountsData');

        Route::post('/add-trading-account', [AccountInfoController::class, 'add_trading_account'])->name('account_info.add_trading_account');
        Route::post('/depositTradingAccount', [AccountInfoController::class, 'depositTradingAccount'])->name('account_info.depositTradingAccount');
        Route::post('/withdrawTradingAccount', [AccountInfoController::class, 'withdrawTradingAccount'])->name('account_info.withdrawTradingAccount');
        Route::post('/internalTransferTradingAccount', [AccountInfoController::class, 'internalTransferTradingAccount'])->name('account_info.internalTransferTradingAccount');
        Route::post('/becomeMaster', [AccountInfoController::class, 'becomeMaster'])->name('account_info.becomeMaster');
        Route::post('/updateMasterConfiguration', [AccountInfoController::class, 'updateMasterConfiguration'])->name('account_info.updateMasterConfiguration');
        Route::post('/updateLeverage', [AccountInfoController::class, 'updateLeverage'])->name('account_info.updateLeverage');
        Route::post('/changePassword', [AccountInfoController::class, 'changePassword'])->name('account_info.changePassword');

//        Route::post('change-leverage', [AccountInfoController::class, 'change_leverage'])->name('account_info.change_leverage');
//
//        Route::get('/getTradingAccounts', [AccountInfoController::class, 'getTradingAccounts'])->name('account_info.getTradingAccounts');
    });

    /**
     * ==============================
     *             HOFI
     * ==============================
     */
    Route::prefix('hofi_strategy')->group(function () {
        // Copy Trading
        Route::get('/copy_trading', [CopyTradingController::class, 'index'])->name('hofi_strategy.master_listing');
        Route::get('/getMasters', [CopyTradingController::class, 'getMasters'])->name('hofi_strategy.getMasters');
        Route::get('/getAvailableAccounts', [CopyTradingController::class, 'getAvailableAccounts'])->name('hofi_strategy.getAvailableAccounts');
        Route::get('/getTerms', [CopyTradingController::class, 'getTerms'])->name('hofi_strategy.getTerms');

        Route::post('joinCopyTrade', [CopyTradingController::class, 'joinCopyTrade'])->name('hofi_strategy.joinCopyTrade');

        // PAMM Trading
        Route::get('/pamm_trading', [PammController::class, 'pamm_master_listing'])->name('hofi_strategy.pamm_trading');
        Route::get('/getPammMasters', [PammController::class, 'getPammMasters'])->name('hofi_strategy.getPammMasters');

        // PAMM ESG
        Route::get('/esg_and_green_finance', [PammController::class, 'esg_investment_portfolio'])->name('hofi_strategy.esg_and_green_finance');
        Route::get('/getPammMasters', [PammController::class, 'getPammMasters'])->name('hofi_strategy.getPammMasters');

        Route::post('/followPammMaster', [PammController::class, 'followPammMaster'])->name('hofi_strategy.followPammMaster');

        // Subscriptions
        Route::get('/subscriptions', [CopyTradingController::class, 'subscriptions'])->name('hofi_strategy.subscriptions');
        Route::get('/getCopyTradeAccounts', [CopyTradingController::class, 'getCopyTradeAccounts'])->name('hofi_strategy.getCopyTradeAccounts');
        Route::get('/getNewMaster', [CopyTradingController::class, 'getNewMaster'])->name('hofi_strategy.getNewMaster');
        Route::get('/getCopyTradeSubscriptions', [CopyTradingController::class, 'getCopyTradeSubscriptions'])->name('hofi_strategy.getCopyTradeSubscriptions');

        Route::get('/getPammAccounts', [PammController::class, 'getPammAccounts'])->name('hofi_strategy.getPammAccounts');
        Route::get('/getPammSubscriptions', [PammController::class, 'getPammSubscriptions'])->name('hofi_strategy.getPammSubscriptions');
        Route::get('/getPammSubscriptionDetail', [PammController::class, 'getPammSubscriptionDetail'])->name('hofi_strategy.getPammSubscriptionDetail');
    });


    /**
     * ==============================
     *             Alpha
     * ==============================
     */
    Route::prefix('alpha_strategy')->group(function () {
        // Copy Trading
        Route::get('/copy_trading', [CopyTradingController::class, 'index'])->name('alpha_strategy.master_listing');
        Route::get('/getMasters', [CopyTradingController::class, 'getMasters'])->name('alpha_strategy.getMasters');
        Route::get('/getAvailableAccounts', [CopyTradingController::class, 'getAvailableAccounts'])->name('alpha_strategy.getAvailableAccounts');
        Route::get('/getTerms', [CopyTradingController::class, 'getTerms'])->name('alpha_strategy.getTerms');

        Route::post('joinCopyTrade', [CopyTradingController::class, 'joinCopyTrade'])->name('alpha_strategy.joinCopyTrade');

        // PAMM Trading
        Route::get('/pamm_trading', [PammController::class, 'pamm_master_listing'])->name('alpha_strategy.pamm_trading');
        Route::get('/getPammMasters', [PammController::class, 'getPammMasters'])->name('alpha_strategy.getPammMasters');

        Route::post('/followPammMaster', [PammController::class, 'followPammMaster'])->name('alpha_strategy.followPammMaster');

        Route::get('/subscriptions', [CopyTradingController::class, 'subscriptions'])->name('alpha_strategy.subscriptions');
        Route::get('/getCopyTradeAccounts', [CopyTradingController::class, 'getCopyTradeAccounts'])->name('alpha_strategy.getCopyTradeAccounts');
        Route::get('/getNewMaster', [CopyTradingController::class, 'getNewMaster'])->name('alpha_strategy.getNewMaster');
        Route::get('/getCopyTradeSubscriptions', [CopyTradingController::class, 'getCopyTradeSubscriptions'])->name('alpha_strategy.getCopyTradeSubscriptions');

        Route::get('/getPammAccounts', [PammController::class, 'getPammAccounts'])->name('alpha_strategy.getPammAccounts');
        Route::get('/getPammSubscriptions', [PammController::class, 'getPammSubscriptions'])->name('alpha_strategy.getPammSubscriptions');
        Route::get('/getPammSubscriptionDetail', [PammController::class, 'getPammSubscriptionDetail'])->name('alpha_strategy.getPammSubscriptionDetail');
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
        Route::get('/subscription_listing', [TradingController::class, 'subscription_listing'])->name('trading.subscription_listing');
        Route::get('/subscription_history', [TradingController::class, 'subscription_history'])->name('trading.subscription_history');
        Route::get('/getCopyTradeTransactions', [TradingController::class, 'getCopyTradeTransactions'])->name('trading.getCopyTradeTransactions');
        Route::get('/getSubscriberAccounts', [TradingController::class, 'getSubscriberAccounts'])->name('trading.getSubscriberAccounts');
        Route::get('/getPenaltyDetail', [TradingController::class, 'getPenaltyDetail'])->name('trading.getPenaltyDetail');
        Route::get('/getSelectedNewMaster', [TradingController::class, 'getSelectedNewMaster'])->name('trading.getSelectedNewMaster');

        Route::post('/subscribeMaster', [TradingController::class, 'subscribeMaster'])->name('trading.subscribeMaster');
        Route::post('/renewalSubscription', [TradingController::class, 'renewalSubscription'])->name('trading.renewalSubscription');
        Route::post('/terminateSubscription', [TradingController::class, 'terminateSubscription'])->name('trading.terminateSubscription');
        Route::post('/terminateBatch', [TradingController::class, 'terminateBatch'])->name('trading.terminateBatch');
        Route::post('/switchMaster', [TradingController::class, 'switchMaster'])->name('trading.switchMaster');

        Route::post('/joinPamm', [PammController::class, 'joinPamm'])->name('pamm.joinPamm');
    });

    /**
     * ==============================
     *             PAMM
     * ==============================
     */
    Route::prefix('pamm')->group(function () {
        // pamm master listing
        Route::get('/pamm_master_listing', [PammController::class, 'pamm_master_listing'])->name('pamm.pamm_master_listing');
        Route::get('/getPammMasters', [PammController::class, 'getPammMasters'])->name('pamm.getPammMasters');
        Route::get('/getMasterSubscriptionPackages', [PammController::class, 'getMasterSubscriptionPackages'])->name('pamm.getMasterSubscriptionPackages');
        Route::get('/master_listing/{masterAccountID}', [TradingController::class, 'masterListingDetail'])->name('pamm.masterListingDetail');


        // esg investment portfolio
        Route::get('/esg_investment_portfolio', [PammController::class, 'esg_investment_portfolio'])->name('pamm.esg_investment_portfolio');

        Route::post('/followPammMaster', [PammController::class, 'followPammMaster'])->name('pamm.followPammMaster');
        Route::post('/topUpPamm', [PammController::class, 'topUpPamm'])->name('pamm.topUpPamm');

        // pamm subscriptions
        Route::get('/pamm_subscriptions', [PammController::class, 'pamm_subscriptions'])->name('pamm.pamm_subscriptions');
        Route::get('/getPammSubscriptionData', [PammController::class, 'getPammSubscriptionData'])->name('pamm.getPammSubscriptionData');
        Route::get('/getPammSubscriptions', [PammController::class, 'getPammSubscriptions'])->name('pamm.getPammSubscriptions');
        Route::get('/getPammSubscriptionDetail', [PammController::class, 'getPammSubscriptionDetail'])->name('pamm.getPammSubscriptionDetail');

        Route::post('/revokePamm', [PammController::class, 'revokePamm'])->name('pamm.revokePamm');
        Route::post('/terminatePammBatch', [PammController::class, 'terminatePammBatch'])->name('pamm.terminatePammBatch');
    });

    /**
     * ==============================
     *         Transaction
     * ==============================
     */
    Route::prefix('transaction')->group(function () {
        Route::get('/transfer_history', [TransactionController::class, 'transfer_history'])->name('transaction.transfer_history');
        Route::get('/getTransferHistory', [TransactionController::class, 'getTransferHistory'])->name('transaction.getTransferHistory');

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
        Route::post('/transfer', [WalletController::class, 'Transfer'])->name('transaction.transfer');

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
        Route::get('/affiliateSubscription', [ReferralController::class, 'affiliateSubscription'])->name('referral.affiliateSubscription');
        Route::get('/affiliateSubscriptionData', [ReferralController::class, 'affiliateSubscriptionData'])->name('referral.affiliateSubscriptionData');
        Route::get('/affiliateListing', [ReferralController::class, 'affiliateListing'])->name('referral.affiliateListing');
        Route::get('/affiliateListingData', [ReferralController::class, 'affiliateListingData'])->name('referral.affiliateListingData');
        Route::get('/getAllCountries', [ReferralController::class, 'getAllCountries'])->name('referral.getAllCountries');
    });

    /**
     * ==============================
     *           Report
     * ==============================
     */
    Route::prefix('report')->group(function () {
        Route::get('/trade_rebate', [ReportController::class, 'tradeRebate'])->name('report.trade_rebate_history');
        Route::get('/getTradeRebateHistories', [ReportController::class, 'getTradeRebateHistories'])->name('report.getTradeRebateHistories');
        Route::get('/wallet_history', [ReportController::class, 'wallet_history'])->name('report.wallet_history');
        Route::get('/getWalletLogs', [ReportController::class, 'getWalletLogs'])->name('report.getWalletLogs');
        Route::get('/trade_history', [ReportController::class, 'trade_history'])->name('report.trade_history');
        Route::get('/getTradeHistories', [ReportController::class, 'getTradeHistories'])->name('report.getTradeHistories');
        Route::get('/performance_incentive', [ReportController::class, 'performance_incentive'])->name('report.performance_incentive');
        Route::get('/getPerformanceIncentive', [ReportController::class, 'getPerformanceIncentive'])->name('report.getPerformanceIncentive');
    });

    /**
     * ==============================
     *           Wallet
     * ==============================
     */
    Route::prefix('wallet')->group(function () {
        Route::get('/wallet_history', [WalletController::class, 'wallet_history'])->name('wallet.wallet_history');
        Route::get('/getWalletHistories', [WalletController::class, 'getWalletHistories'])->name('wallet.getWalletHistories');
    });

});

Route::get('/components/buttons', function () {
    return Inertia::render('Components/Buttons');
})->middleware(['auth', 'verified'])->name('components.buttons');

require __DIR__ . '/auth.php';
