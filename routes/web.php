<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\AppProxyController;

Route::get('/', [DashboardController::class, 'index'])->middleware(['verify.shopify'])->name('home');
Route::post('/connect', [DashboardController::class, 'connect'])->middleware(['verify.shopify'])->name('connect');
Route::post('/disconnect', [DashboardController::class, 'disconnect'])->middleware(['verify.shopify'])->name('disconnect');

Route::get('/help', function () {
    return view('help');
})->middleware(['verify.shopify'])->name('help');


Route::get('/proxy', 'AppProxyController@index')->middleware('auth.proxy');