<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use App\Models\StoreConnection;
use App\Jobs\SyncCustomerJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomersUpdateJob implements ShouldQueue
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
        $customerData = $this->convertToArray($this->data);

        if ($this->isSyncedCustomer($customerData)) {
            return;
        }

        Log::info("Customer updated in {$this->shopDomain->toNative()}: " . json_encode($customerData));

        $this->syncCustomerUpdateToConnectedStores($customerData);
    }

    protected function syncCustomerUpdateToConnectedStores($customerData)
    {
        $sourceShopDomain = $this->shopDomain->toNative();
        $storeConnection = StoreConnection::where('shop_domain', $sourceShopDomain)->first();

        if ($storeConnection) {
            $connectedShops = $storeConnection->connectedStores;
            foreach ($connectedShops as $connectedShop) {
                $uniqueJobIdentifier = 'sync_customer_' . $customerData['id'] . '_' . $connectedShop->shop_domain;
                $existingJob = DB::table('jobs')
                    ->where('payload', 'like', "%{$uniqueJobIdentifier}%")
                    ->exists();

                if (!$existingJob) {
                    SyncCustomerJob::dispatch(
                        $sourceShopDomain,
                        $connectedShop->shop_domain,
                        $customerData,
                        true
                    );
                }
            }
        }
    }

    private function isSyncedCustomer($customerData)
    {
        return isset($customerData['tags']) && strpos($customerData['tags'], 'source:') !== false;
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