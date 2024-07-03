<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebhookController;

Route::get('/', [DashboardController::class, 'index'])->middleware(['verify.shopify'])->name('home');
Route::post('/connect', [DashboardController::class, 'connect'])->middleware(['verify.shopify'])->name('connect');
Route::post('/disconnect', [DashboardController::class, 'disconnect'])->middleware(['verify.shopify'])->name('disconnect');

// Update this line
Route::post('/webhook/app-uninstalled', [WebhookController::class, 'handleAppUninstalled'])->name('webhook.app-uninstalled');