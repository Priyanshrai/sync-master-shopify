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



// Route::get('/proxy', [AppProxyController::class, 'getConnectionId'])->middleware(['auth.proxy']);
// Route::get('/proxy-test', [AppProxyController::class, 'test']);
Route::get('connectionid', function (Request $request) {
    \Log::info('Proxy route accessed', $request->all());
    $shop = auth()->user();
    if (!$shop) {
        $shop = \App\Models\User::where('name', $request->get('shop'))->first();
    }
    if (!$shop) {
        return response('Unauthorized', 401);
    }
    $connection = \App\Models\StoreConnection::where('shop_domain', $shop->name)->first();
    if (!$connection) {
        return response('No connection found', 404);
    }
    return response($connection->connection_id)->header('Content-Type', 'text/plain');
})->middleware('auth.proxy');


Route::any('{any}', function (Request $request) {
    \Log::info('Catch-all route hit', [
        'path' => $request->path(),
        'method' => $request->method(),
        'all' => $request->all()
    ]);
    return response('Catch-all route');
})->where('any', '.*');