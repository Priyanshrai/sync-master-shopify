<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use App\Models\StoreConnection;
use App\Jobs\SyncOrderJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdersUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shopDomain;
    public $data;

    public function __construct($shopDomain, $data)
    {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
    }

    public function handle()
    {
        $this->shopDomain = ShopDomain::fromNative($this->shopDomain);

        // Convert data to array if it's an object
        $orderData = $this->convertToArray($this->data);

        if ($this->isSyncedOrder($orderData)) {
            return;
        }

        Log::info("Order updated in {$this->shopDomain->toNative()}: " . json_encode($orderData));

        $this->syncOrderUpdateToConnectedStores($orderData);
    }

    protected function syncOrderUpdateToConnectedStores($orderData)
    {
        $sourceShopDomain = $this->shopDomain->toNative();
        $storeConnection = StoreConnection::where('shop_domain', $sourceShopDomain)->first();

        if ($storeConnection) {
            $connectedShops = $storeConnection->connectedStores;
            foreach ($connectedShops as $connectedShop) {
                $uniqueJobIdentifier = 'sync_order_' . $orderData['id'] . '_' . $connectedShop->shop_domain;
                $existingJob = DB::table('jobs')
                    ->where('payload', 'like', "%{$uniqueJobIdentifier}%")
                    ->exists();

                if (!$existingJob) {
                    SyncOrderJob::dispatch(
                        $sourceShopDomain,
                        $connectedShop->shop_domain,
                        $orderData,
                        true
                    );
                }
            }
        }
    }

    private function isSyncedOrder($orderData)
    {
        return isset($orderData['tags']) && strpos($orderData['tags'], 'source:') !== false;
    }

    private function convertToArray($data)
    {
        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                return $data->toArray();
            }
            return json_decode(json_encode($data), true);
        }
        return $data;
    }
}