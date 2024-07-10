<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;
use App\Models\StoreConnection;
use App\Jobs\SyncProductJob;
use Illuminate\Support\Facades\DB;

class ProductsUpdateJob implements ShouldQueue
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

        // Check if the product is being synced
        if ($this->isSyncedProduct()) {
            return;
        }

        // Log the event
        \Log::info("Product updated in {$this->shopDomain->toNative()}: " . json_encode($this->data));

        // Sync this product update to connected stores
        $this->syncProductUpdateToConnectedStores();
    }

    protected function syncProductUpdateToConnectedStores()
    {
        $sourceShopDomain = $this->shopDomain->toNative();
        $storeConnection = StoreConnection::where('shop_domain', $sourceShopDomain)->first();

        if ($storeConnection) {
            $connectedShops = $storeConnection->connectedStores;

            foreach ($connectedShops as $connectedShop) {
                $uniqueJobIdentifier = 'sync_product_' . $this->data->id . '_' . $connectedShop->shop_domain;

                $existingJob = DB::table('jobs')
                    ->where('payload', 'like', "%{$uniqueJobIdentifier}%")
                    ->exists();

                if (!$existingJob) {
                    SyncProductJob::dispatch(
                        $sourceShopDomain,
                        $connectedShop->shop_domain,
                        $this->data,
                        true
                    );
                }
            }
        }
    }

    private function isSyncedProduct()
    {
        return isset($this->data->tags) && strpos($this->data->tags, 'source:') !== false;
    }
}