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
        $this->productData = $productData;
        $this->isUpdate = $isUpdate;
        $this->uniqueJobIdentifier = 'sync_product_' . $productData->id . '_' . $targetShopDomain;
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

        if (empty($this->productData) || !is_object($this->productData)) {
            Log::error("Invalid product data", [
                'source_shop' => $this->shopDomain,
                'target_shop' => $this->targetShopDomain,
                'product_data' => $this->productData
            ]);
            return;
        }

        $product = $this->productData;

        if (!isset($product->id)) {
            Log::error("Product ID is missing", [
                'source_shop' => $this->shopDomain,
                'target_shop' => $this->targetShopDomain,
                'product_data' => $product
            ]);
            return;
        }

        // Check if the product has already been synced
        $existingSyncedItem = SyncedItem::where('item_type', 'product')
            ->where('item_id', $product->id)
            ->where('source_shop_domain', $this->shopDomain)
            ->where('target_shop_domain', $this->targetShopDomain)
            ->first();

        if ($existingSyncedItem) {
            Log::info('Product already synced', [
                'product_id' => $product->id,
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

        $product->tags = $this->appendSourceTag($product->tags ?? '', $this->shopDomain);

        try {
            if ($this->isUpdate) {
                $response = $targetShop->api()->rest('PUT', '/admin/api/2023-04/products/' . $product->id . '.json', ['product' => (array)$product]);
            } else {
                $response = $targetShop->api()->rest('POST', '/admin/api/2023-04/products.json', ['product' => (array)$product]);
            }

            Log::info('Product synced to target shop', [
                'product_id' => $product->id,
                'target_shop' => $this->targetShopDomain,
                'response_status' => $response['status'],
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);

            // Create a new synced item entry
            SyncedItem::create([
                'item_type' => 'product',
                'item_id' => $product->id,
                'source_shop_domain' => $this->shopDomain,
                'target_shop_domain' => $this->targetShopDomain
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing product', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
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