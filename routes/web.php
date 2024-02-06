<?php

use App\Http\Controllers\AccountInfoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\TransactionController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ProfileController;

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

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/getBalanceChart', [DashboardController::class, 'getBalanceChart']);
    Route::post('/update_session', [DashboardController::class, 'update_session']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /**
     * ==============================
     *         Account Info
     * ==============================
     */

    Route::prefix('account_info')->group(function () {
        Route::get('/account_listing', [AccountInfoController::class, 'index'])->name('account_info.account_info');
        Route::get('/refreshTradingAccountsData', [AccountInfoController::class, 'refreshTradingAccountsData'])->name('account_info.refreshTradingAccountsData');
        Route::post('/add-trading-account', [AccountInfoController::class, 'add_trading_account'])->name('account_info.add_trading_account');

//        Route::post('change-leverage', [AccountInfoController::class, 'change_leverage'])->name('account_info.change_leverage');
//
//        Route::get('/getTradingAccounts', [AccountInfoController::class, 'getTradingAccounts'])->name('account_info.getTradingAccounts');
    });

    /**
     * ==============================
     *         Transaction
     * ==============================
     */
    Route::prefix('transaction')->group(function () {
        Route::get('/transaction_listing', [TransactionController::class, 'index'])->name('transaction.transaction_listing');
        Route::get('/getTransactionData/{category}', [TransactionController::class, 'getTransactionData'])->name('transaction.getTransactionData');
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
