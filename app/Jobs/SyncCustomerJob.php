<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\SyncedItem;
use Illuminate\Support\Facades\Log;

class SyncCustomerJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shopDomain;
    public $targetShopDomain;
    public $customerData;
    public $isUpdate;
    public $uniqueJobIdentifier;

    public function __construct($shopDomain, $targetShopDomain, $customerData, $isUpdate = false)
    {
        $this->shopDomain = $shopDomain;
        $this->targetShopDomain = $targetShopDomain;
        $this->customerData = is_string($customerData) ? json_decode($customerData, true) : $customerData;
        $this->isUpdate = $isUpdate;
        $this->uniqueJobIdentifier = 'sync_customer_' . $this->customerData['id'] . '_' . $targetShopDomain;
    }

    public function uniqueId()
    {
        return $this->uniqueJobIdentifier;
    }

    public function handle()
    {
        Log::info("Starting SyncCustomerJob", [
            'source_shop' => $this->shopDomain,
            'target_shop' => $this->targetShopDomain,
            'is_update' => $this->isUpdate,
            'customer_data' => $this->customerData
        ]);

        if (empty($this->customerData) || !isset($this->customerData['id'])) {
            Log::error("Invalid customer data", [
                'source_shop' => $this->shopDomain,
                'target_shop' => $this->targetShopDomain,
                'customer_data' => $this->customerData
            ]);
            return;
        }

        // Check if the customer has already been synced
        $existingSyncedItem = SyncedItem::where('item_type', 'customer')
            ->where('item_id', $this->customerData['id'])
            ->where('source_shop_domain', $this->shopDomain)
            ->where('target_shop_domain', $this->targetShopDomain)
            ->first();

        if ($existingSyncedItem && !$this->isUpdate) {
            Log::info('Customer already synced', [
                'customer_id' => $this->customerData['id'],
                'source_shop' => $this->shopDomain,
                'target_shop' => $this->targetShopDomain
            ]);
            return;
        }

        $targetShop = User::where('name', $this->targetShopDomain)->first();

        if (!$targetShop) {
            Log::error("Target shop not found", [
                'target_shop' => $this->targetShopDomain,
                'source_shop' => $this->shopDomain
            ]);
            return;
        }

        $this->customerData['tags'] = $this->appendSourceTag($this->customerData['tags'] ?? '', $this->shopDomain);

        try {
            if ($this->isUpdate) {
                $response = $targetShop->api()->rest('PUT', '/admin/api/2023-04/customers/' . $this->customerData['id'] . '.json', ['customer' => $this->customerData]);
            } else {
                $response = $targetShop->api()->rest('POST', '/admin/api/2023-04/customers.json', ['customer' => $this->customerData]);
            }

            Log::info('Customer synced to target shop', [
                'customer_id' => $this->customerData['id'],
                'target_shop' => $this->targetShopDomain,
                'response_status' => $response['status'],
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);

            // Create or update synced item entry
            SyncedItem::updateOrCreate(
                [
                    'item_type' => 'customer',
                    'item_id' => $this->customerData['id'],
                    'source_shop_domain' => $this->shopDomain,
                    'target_shop_domain' => $this->targetShopDomain
                ],
                ['last_synced_at' => now()]
            );
        } catch (\Exception $e) {
            Log::error('Error syncing customer', [
                'error' => $e->getMessage(),
                'customer_id' => $this->customerData['id'],
                'target_shop' => $this->targetShopDomain,
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);
        }
    }

    private function appendSourceTag($tags, $sourceShopDomain)
    {
        $sourceTag = "source:{$sourceShopDomain}";
        $tagArray = array_map('trim', explode(',', $tags));
        if (!in_array($sourceTag, $tagArray)) {
            $tagArray[] = $sourceTag;
        }
        return implode(', ', $tagArray);
    }
}