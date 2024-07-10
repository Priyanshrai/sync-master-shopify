<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use App\Models\StoreConnection;
use App\Jobs\SyncProductJob;
use Illuminate\Support\Facades\DB;

class ProductsCreateJob implements ShouldQueue
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
        // Convert domain
        $this->shopDomain = ShopDomain::fromNative($this->shopDomain);

        // Convert data to array if it's an object
        $productData = $this->convertToArray($this->data);

        // Check if the product is being synced
        if ($this->isSyncedProduct($productData)) {
            return;
        }

        // Log the event
        \Log::info("Product created in {$this->shopDomain->toNative()}: " . json_encode($productData));

        // Sync this product to connected stores
        $this->syncProductToConnectedStores($productData);
    }

    protected function syncProductToConnectedStores($productData)
    {
        $sourceShopDomain = $this->shopDomain->toNative();
        $storeConnection = StoreConnection::where('shop_domain', $sourceShopDomain)->first();

        if ($storeConnection) {
            $connectedShops = $storeConnection->connectedStores;

            foreach ($connectedShops as $connectedShop) {
                $uniqueJobIdentifier = 'sync_product_' . $productData['id'] . '_' . $connectedShop->shop_domain;

                $existingJob = DB::table('jobs')
                    ->where('payload', 'like', "%{$uniqueJobIdentifier}%")
                    ->exists();

                if (!$existingJob) {
                    SyncProductJob::dispatch(
                        $sourceShopDomain,
                        $connectedShop->shop_domain,
                        $productData,
                        false
                    );
                }
            }
        }
    }

    private function isSyncedProduct($productData)
    {
        return isset($productData['tags']) && strpos($productData['tags'], 'source:') !== false;
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