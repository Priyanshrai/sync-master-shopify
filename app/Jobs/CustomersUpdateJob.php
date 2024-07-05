<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

class CustomersUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Shop's myshopify domain
     *
     * @var ShopDomain|string
     */
    public $shopDomain;

    /**
     * The webhook data
     *
     * @var object
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param string   $shopDomain The shop's myshopify domain.
     * @param stdClass $data       The webhook data (JSON decoded).
     *
     * @return void
     */
    public function __construct($shopDomain, $data)
    {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Convert domain
        $this->shopDomain = ShopDomain::fromNative($this->shopDomain);

        // Log the event
        \Log::info("Customer updated in {$this->shopDomain->toNative()}: " . json_encode($this->data));

        // Sync this customer update to connected stores
        $this->syncCustomerUpdateToConnectedStores();
    }

    /**
     * Sync the customer update to connected stores.
     *
     * @return void
     */
    protected function syncCustomerUpdateToConnectedStores()
    {
        $sourceShopDomain = $this->shopDomain->toNative();
        $storeConnection = \App\Models\StoreConnection::where('shop_domain', $sourceShopDomain)->first();

        if ($storeConnection) {
            $connectedShops = $storeConnection->connectedStores;
            foreach ($connectedShops as $connectedShop) {
                // Create a new job to sync this customer update to the connected shop
                SyncCustomerJob::dispatch($this->data, $sourceShopDomain, $connectedShop->shop_domain, true);
            }
        }
    }
}