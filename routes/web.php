<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\AppProxyController;

Route::middleware(['verify.shopify'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::post('/connect', [DashboardController::class, 'connect'])->name('connect');
    Route::post('/disconnect', [DashboardController::class, 'disconnect'])->name('disconnect');
    Route::get('/help', function () {
        return view('help');
    })->name('help');
});

Route::get('/proxy', [AppProxyController::class, 'handleProxy'])->middleware('auth.proxy');