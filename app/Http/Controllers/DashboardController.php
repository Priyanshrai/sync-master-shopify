<?php

namespace App\Http\Controllers;

use App\Models\StoreConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use App\Jobs\SyncStoreData;

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

        $connectedShops = $connection->connectedStores->pluck('shop_domain')->toArray();

        return view('dashboard', [
            'connectionId' => $connection->connection_id,
            'connectedShops' => $connectedShops,
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
            $shopDomain = $shop->getDomain()->toNative();
            $connection = StoreConnection::where('shop_domain', $shopDomain)->firstOrFail();
            $targetConnection = StoreConnection::where('connection_id', $request->connection_id)->firstOrFail();
    
            if ($connection->id === $targetConnection->id) {
                return response()->json(['error' => 'Cannot connect to your own store'], 400);
            }
    
            if ($connection->connectedStores->contains($targetConnection->shop_domain)) {
                return response()->json(['error' => 'Already connected to this store'], 400);
            }
    
            $connection->connectedStores()->attach($targetConnection->shop_domain);
            $targetConnection->connectedStores()->attach($shopDomain);
    
            // Dispatch the sync job
            SyncStoreData::dispatch($shop->getDomain()->toNative(), $targetConnection->shop_domain);
            // SyncStoreData::dispatch($shop, $targetConnection->shop);
            \Log::info('SyncStoreData job dispatched', [
                'source_shop' => $shopDomain,
                'target_shop' => $targetConnection->shop_domain,
            ]);
    
              
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
            $shopDomain = $shop->getDomain()->toNative();
            $connection = StoreConnection::where('shop_domain', $shopDomain)->firstOrFail();

            $targetShopDomain = $request->input('shop_domain');
            if (!$targetShopDomain) {
                return response()->json(['error' => 'Target shop domain is required'], 400);
            }

            $targetConnection = StoreConnection::where('shop_domain', $targetShopDomain)->first();
            if (!$targetConnection) {
                return response()->json(['error' => 'Target shop not found'], 404);
            }

            $connection->connectedStores()->detach($targetShopDomain);
            $targetConnection->connectedStores()->detach($shopDomain);

            \Log::info('Store disconnection successful', ['current_shop' => $shopDomain, 'disconnected_from' => $targetShopDomain]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Store disconnection failed: ' . $e->getMessage());
            return response()->json(['error' => 'Store disconnection failed. Please try again.'], 500);
        }
    }
}