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

class SyncProductJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shopDomain;
    public $targetShopDomain;
    public $productData;
    public $isUpdate;
    public $uniqueJobIdentifier;

    public function __construct($shopDomain, $targetShopDomain, $productData, $isUpdate = false)
    {
        $this->shopDomain = $shopDomain;
        $this->targetShopDomain = $targetShopDomain;
        $this->productData = is_string($productData) ? json_decode($productData, true) : $productData;
        $this->isUpdate = $isUpdate;
        $this->uniqueJobIdentifier = 'sync_product_' . ($this->productData['id'] ?? '') . '_' . $targetShopDomain;
    }

    public function uniqueId()
    {
        return $this->uniqueJobIdentifier;
    }

    public function handle()
    {
        Log::info("Starting SyncProductJob", [
            'source_shop' => $this->shopDomain,
            'target_shop' => $this->targetShopDomain,
            'is_update' => $this->isUpdate,
            'product_data' => $this->productData
        ]);

        if (empty($this->productData) || !isset($this->productData['id'])) {
            Log::error("Invalid product data", [
                'source_shop' => $this->shopDomain,
                'target_shop' => $this->targetShopDomain,
                'product_data' => $this->productData
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

        $this->productData['tags'] = $this->appendSourceTag($this->productData['tags'] ?? '', $this->shopDomain);

        try {
            if ($this->isUpdate) {
                $response = $targetShop->api()->rest('PUT', '/admin/api/2023-07/products/' . $this->productData['id'] . '.json', ['product' => $this->productData]);
            } else {
                $response = $targetShop->api()->rest('POST', '/admin/api/2023-07/products.json', ['product' => $this->productData]);
            }

            Log::info('Product synced to target shop', [
                'product_id' => $this->productData['id'],
                'target_shop' => $this->targetShopDomain,
                'response_status' => $response['status'],
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);

            SyncedItem::updateOrCreate(
                [
                    'item_type' => 'product',
                    'item_id' => $this->productData['id'],
                    'source_shop_domain' => $this->shopDomain,
                    'target_shop_domain' => $this->targetShopDomain
                ],
                ['last_synced_at' => now()]
            );
        } catch (\Exception $e) {
            Log::error('Error syncing product', [
                'error' => $e->getMessage(),
                'product_id' => $this->productData['id'],
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