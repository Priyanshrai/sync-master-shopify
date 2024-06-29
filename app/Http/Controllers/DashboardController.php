<?php

namespace App\Http\Controllers;

use App\Models\StoreConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $shop = $request->user();
        $shopDomain = $shop->getDomain()->toNative();
        
        $connection = StoreConnection::firstOrCreate(
            ['shop_domain' => $shopDomain],
            ['connection_id' => $this->generateUniqueConnectionId()]
        );

        return view('dashboard', [
            'connectionId' => $connection->connection_id,
            'connectedShop' => $connection->connected_to,
            'shopDomain' => $shopDomain
        ]);
    }

    private function generateUniqueConnectionId()
    {
        do {
            $connectionId = Str::random(10);
        } while (StoreConnection::where('connection_id', $connectionId)->exists());

        return $connectionId;
    }

    public function connect(Request $request)
    {
        try {
            \Log::info('Connect request received', ['request_data' => $request->all()]);
            
            $request->validate([
                'connection_id' => [
                    'required',
                    'size:10',
                    Rule::exists('store_connections', 'connection_id')->where(function ($query) use ($request) {
                        $query->where('shop_domain', '!=', $request->user()->getDomain()->toNative());
                    }),
                ],
            ], [
                'connection_id.required' => 'Connection ID is required.',
                'connection_id.size' => 'Connection ID must be exactly 10 characters long.',
                'connection_id.exists' => 'Invalid Connection ID or you cannot connect to your own store.',
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

            if ($targetConnection->connected_to) {
                return response()->json(['error' => 'The target store is already connected to another store'], 400);
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()['connection_id'][0]], 400);
        } catch (\Exception $e) {
            \Log::error('Store connection failed: ' . $e->getMessage());
            return response()->json(['error' => 'Store connection failed. Please try again.'], 500);
        }
    }
    
    public function disconnect(Request $request)
    {
        try {
            $shop = $request->user();
            if (!$shop) {
                \Log::error('User not authenticated in disconnect method');
                return response()->json(['error' => 'Authentication failed'], 401);
            }

            $shopDomain = $shop->getDomain()->toNative();
            $connection = StoreConnection::where('shop_domain', $shopDomain)->firstOrFail();

            if (!$connection->connected_to) {
                return response()->json(['error' => 'Your store is not connected to any other store'], 400);
            }

            $targetConnection = StoreConnection::where('shop_domain', $connection->connected_to)->first();
            if ($targetConnection) {
                $targetConnection->connected_to = null;
                $targetConnection->save();
            }

            $connection->connected_to = null;
            $connection->save();

            \Log::info('Store disconnection successful', ['current_shop' => $shopDomain]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Store disconnection failed: ' . $e->getMessage());
            return response()->json(['error' => 'Store disconnection failed. Please try again.'], 500);
        }
    }
}