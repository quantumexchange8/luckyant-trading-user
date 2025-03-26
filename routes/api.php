<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\PammController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MasterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::get('/getMasters', function () {
//     return \App\Models\Master::latest()->get();
// });

Route::get('getMaster', [MasterController::class, 'getMaster']);
Route::get('getLiveAccount', [MasterController::class, 'getLiveAccount']);
Route::get('getMasterGrowth', [MasterController::class, 'getMasterGrowth']);
Route::get('getMasterLatestTrades', [MasterController::class, 'getMasterLatestTrades']);
Route::get('getMasterCurrency', [MasterController::class, 'getMasterCurrency']);
Route::get('getMasterOpenTrade', [MasterController::class, 'getMasterOpenTrade']);

/**
 * ==============================
 *             Account
 * ==============================
 */
Route::prefix('account')->group(function () {
    Route::post('create_account', [AccountController::class, 'create_account']);
    Route::post('deposit_account', [AccountController::class, 'deposit_account']);
});

/**
 * ==============================
 *             PAMM
 * ==============================
 */
Route::prefix('pamm')->group(function () {
    Route::get('getStrategies', [PammController::class, 'getStrategies']);
    Route::get('getStrategySummary', [PammController::class, 'getStrategySummary']);
    Route::get('getStrategyDetails', [PammController::class, 'getStrategyDetails']);

    Route::post('join_investment_strategy', [PammController::class, 'join_investment_strategy']);
    Route::post('revoke_investment_strategy', [PammController::class, 'revoke_investment_strategy']);
    Route::post('withdrawStrategyProfit', [PammController::class, 'withdrawStrategyProfit']);
    Route::post('withdraw_balance', [PammController::class, 'withdrawBalance']);
    Route::post('terminate_investment_strategy', [PammController::class, 'terminate_investment_strategy']);
});

// New Routes with Token
Route::middleware('api.token')->group(function () {
    Route::post('sync_user_account', [UserController::class, 'sync_user_account']);
});
