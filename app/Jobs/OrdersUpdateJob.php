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

        if ($this->isSyncedOrder()) {
            return;
        }

        Log::info("Order updated in {$this->shopDomain->toNative()}: " . json_encode($this->data));

        $this->syncOrderUpdateToConnectedStores();
    }

    protected function syncOrderUpdateToConnectedStores()
    {
        $sourceShopDomain = $this->shopDomain->toNative();
        $storeConnection = StoreConnection::where('shop_domain', $sourceShopDomain)->first();

        if ($storeConnection) {
            $connectedShops = $storeConnection->connectedStores;
            foreach ($connectedShops as $connectedShop) {
                $uniqueJobIdentifier = 'sync_order_' . $this->data->id . '_' . $connectedShop->shop_domain;
                $existingJob = DB::table('jobs')
                    ->where('payload', 'like', "%{$uniqueJobIdentifier}%")
                    ->exists();

                if (!$existingJob) {
                    SyncOrderJob::dispatch(
                        $sourceShopDomain,
                        $connectedShop->shop_domain,
                        $this->data,
                        true
                    );
                }
            }
        }
    }

    private function isSyncedOrder()
    {
        return isset($this->data->tags) && strpos($this->data->tags, 'source:') !== false;
    }
}