<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SyncCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shopDomain;
    public $targetShopDomain;
    public $customerData;
    public $isUpdate;

    public function __construct($shopDomain, $customerData, $isUpdate = false)
    {
        $this->shopDomain = $shopDomain;
        $this->customerData = is_string($customerData) ? json_decode($customerData, true) : $customerData;
        $this->isUpdate = $isUpdate;
    }

    public function handle()
    {
        $targetShop = User::where('name', $this->targetShopDomain)->first();
        
        if (!$targetShop) {
            \Log::error("Target shop not found: {$this->targetShopDomain}");
            return;
        }

        $customer = $this->customerData;
        $customer['tags'] = isset($customer['tags']) ? $customer['tags'] . ", source:{$this->shopDomain}" : "source:{$this->shopDomain}";

        try {
            if ($this->isUpdate) {
                $response = $targetShop->api()->rest('PUT', '/admin/api/2023-04/customers/' . $customer['id'] . '.json', ['customer' => $customer]);
            } else {
                $response = $targetShop->api()->rest('POST', '/admin/api/2023-04/customers.json', ['customer' => $customer]);
            }
            \Log::info('Customer synced to target shop', [
                'customer_id' => $customer['id'],
                'target_shop' => $this->targetShopDomain,
                'response_status' => $response['status'],
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error syncing customer', [
                'error' => $e->getMessage(),
                'customer_id' => $customer['id'],
                'target_shop' => $this->targetShopDomain,
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);
        }
    }
}