<?php

namespace App\Http\Controllers;

use App\Models\StoreConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $shop = $request->user();
        $shopDomain = $shop->getDomain()->toNative();
        
        $connection = StoreConnection::firstOrCreate(
            ['shop_domain' => $shopDomain],
            ['connection_id' => Str::random(10)]
        );

        return view('dashboard', [
            'connectionId' => $connection->connection_id,
            'connectedShop' => $connection->connected_to,
            'shopDomain' => $shopDomain
        ]);
    }

    public function connect(Request $request)
    {
        try {
            \Log::info('Connect request received', ['request_data' => $request->all()]);

            $request->validate([
                'connection_id' => 'required|exists:store_connections,connection_id',
            ]);

            $shop = $request->user();
            if (!$shop) {
                \Log::error('User not authenticated in connect method');
                return response()->json(['error' => 'Authentication failed'], 401);
            }

            $shopDomain = $shop->getDomain()->toNative();
            $connection = StoreConnection::where('shop_domain', $shopDomain)->firstOrFail();
            $targetConnection = StoreConnection::where('connection_id', $request->connection_id)->firstOrFail();

            if ($connection->id === $targetConnection->id) {
                return response()->json(['error' => 'Cannot connect to your own store'], 400);
            }

            $connection->connected_to = $targetConnection->shop_domain;
            $connection->save();

            $targetConnection->connected_to = $shopDomain;
            $targetConnection->save();

            \Log::info('Store connection successful', [
                'current_shop' => $shopDomain,
                'connected_to' => $targetConnection->shop_domain,
            ]);

            return response()->json(['success' => true, 'connected_shop' => $targetConnection->shop_domain]);
        } catch (\Exception $e) {
            \Log::error('Store connection failed: ' . $e->getMessage());
            return response()->json(['error' => 'Store connection failed'], 500);
        }
    }
    
    public function disconnect(Request $request)
    {
        $shop = $request->user();
        $shopDomain = $shop->getDomain()->toNative();
        $connection = StoreConnection::where('shop_domain', $shopDomain)->firstOrFail();

        if ($connection->connected_to) {
            $targetConnection = StoreConnection::where('shop_domain', $connection->connected_to)->first();
            if ($targetConnection) {
                $targetConnection->connected_to = null;
                $targetConnection->save();
            }
            $connection->connected_to = null;
            $connection->save();
        }

        return response()->json(['success' => true]);
    }
}