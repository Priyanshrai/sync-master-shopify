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
use App\Jobs\SyncCustomerJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomersCreateJob implements ShouldQueue
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

        if ($this->isSyncedCustomer()) {
            return;
        }

        Log::info("Customer created in {$this->shopDomain->toNative()}: " . json_encode($this->data));

        $this->syncCustomerToConnectedStores();
    }

    protected function syncCustomerToConnectedStores()
    {
        $sourceShopDomain = $this->shopDomain->toNative();
        $storeConnection = StoreConnection::where('shop_domain', $sourceShopDomain)->first();

        if ($storeConnection) {
            $connectedShops = $storeConnection->connectedStores;
            foreach ($connectedShops as $connectedShop) {
                $uniqueJobIdentifier = 'sync_customer_' . $this->data->id . '_' . $connectedShop->shop_domain;
                $existingJob = DB::table('jobs')
                    ->where('payload', 'like', "%{$uniqueJobIdentifier}%")
                    ->exists();

                if (!$existingJob) {
                    SyncCustomerJob::dispatch(
                        $sourceShopDomain,
                        $connectedShop->shop_domain,
                        $this->data,
                        false
                    );
                }
            }
        }
    }

    private function isSyncedCustomer()
    {
        return isset($this->data->tags) && strpos($this->data->tags, 'source:') !== false;
    }
}