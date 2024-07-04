<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppProxyController extends Controller
{
    public function index(Request $request)
{
    \Log::info('App proxy request received', ['shop' => $request->get('shop')]);

    $shop = $request->get('shop');
    $connection = \App\Models\StoreConnection::where('shop_domain', $shop)->first();

    if (!$connection) {
        \Log::warning('Connection not found for shop', ['shop' => $shop]);
        return response()->json(['error' => 'Connection not found'], 404);
    }

    \Log::info('Returning connection ID', ['connectionId' => $connection->connection_id]);
    return response()->json(['connectionId' => $connection->connection_id]);
}
}
