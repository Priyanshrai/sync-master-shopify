<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StoreConnection;

class AppProxyController extends Controller
{
    public function getConnectionId(Request $request)
    {
        \Log::info('Proxy route accessed');
        try {
            $shop = $request->user();
            if (!$shop) {
                \Log::error('No shop found in proxy request');
                return response('Unauthorized', 401);
            }
            
            $shopDomain = $shop->getDomain()->toNative();
            \Log::info('Shop domain: ' . $shopDomain);
            
            $connection = StoreConnection::where('shop_domain', $shopDomain)->first();
            
            if (!$connection) {
                \Log::error('No connection found for shop: ' . $shopDomain);
                return response('No connection found', 404);
            }
            
            \Log::info('Connection ID: ' . $connection->connection_id);
            return response($connection->connection_id)->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            \Log::error('Error in getConnectionId: ' . $e->getMessage());
            return response('Error fetching connection ID: ' . $e->getMessage(), 500);
        }
    }
    public function test()
{
    \Log::info('Proxy test route accessed');
    return response('Proxy test successful')->header('Content-Type', 'text/plain');
}
}