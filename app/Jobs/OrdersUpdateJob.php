<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

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
        \Log::info("Order updated in {$this->shopDomain->toNative()}: " . json_encode($this->data));
        $this->syncOrderToConnectedStores();
    }

    protected function syncOrderToConnectedStores()
    {
        $sourceShopDomain = $this->shopDomain->toNative();
        $storeConnection = \App\Models\StoreConnection::where('shop_domain', $sourceShopDomain)->first();

        if ($storeConnection) {
            foreach ($storeConnection->connectedStores as $connectedShop) {
                SyncOrderJob::dispatch($this->data, $sourceShopDomain, $connectedShop->shop_domain, true);
            }
        }
    }
}