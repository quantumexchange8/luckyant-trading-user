<?php

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
Route::get('getMasterLiveTrades', [MasterController::class, 'getMasterLiveTrades']);
