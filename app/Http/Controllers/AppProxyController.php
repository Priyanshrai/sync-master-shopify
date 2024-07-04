<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StoreConnection;

class AppProxyController extends Controller
{
    public function index()
    {
        $shop = auth()->user();
        $shopDomain = $shop->getDomain()->toNative();
        $connection = \App\Models\StoreConnection::where('shop_domain', $shopDomain)->first();
        
        if (!$connection) {
            return response()->json(['error' => 'Connection not found'], 404);
        }
    
        return response()->json(['connectionId' => $connection->connection_id])
            ->withHeaders(['Content-Type' => 'application/liquid']);
    }
}