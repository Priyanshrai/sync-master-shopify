<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StoreConnection;

class AppProxyController extends Controller
{
    public function handleProxy(Request $request)
    {
        \Log::info('Proxy route accessed', $request->all());
        
        $shop = auth()->user();
        if (!$shop) {
            $shop = User::where('name', $request->get('shop'))->first();
        }
        
        if (!$shop) {
            return response('Unauthorized', 401);
        }
        
        $connection = StoreConnection::where('shop_domain', $shop->name)->first();
        
        if (!$connection) {
            return response('No connection found', 404);
        }
        
        return response($connection->connection_id)->header('Content-Type', 'text/plain');
    }
}