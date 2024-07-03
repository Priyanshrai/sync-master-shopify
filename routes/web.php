
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;



Route::get('/', [DashboardController::class, 'index'])->middleware(['verify.shopify'])->name('home');
Route::post('/connect', [DashboardController::class, 'connect'])->middleware(['verify.shopify'])->name('connect');
Route::post('/disconnect', [DashboardController::class, 'disconnect'])->middleware(['verify.shopify'])->name('disconnect');