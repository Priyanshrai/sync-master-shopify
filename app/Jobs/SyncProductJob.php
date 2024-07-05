<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SyncProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shopDomain;
    public $targetShopDomain;
    public $productData;
    public $isUpdate;

    public function __construct($shopDomain, $productData, $isUpdate = false)
    {
        $this->shopDomain = $shopDomain;
        $this->productData = is_string($productData) ? json_decode($productData, true) : $productData;
        $this->isUpdate = $isUpdate;
    }

    public function handle()
    {
        $targetShop = User::where('name', $this->targetShopDomain)->first();
        
        if (!$targetShop) {
            \Log::error("Target shop not found: {$this->targetShopDomain}");
            return;
        }

        $product = $this->productData;
        $product['tags'] = isset($product['tags']) ? $product['tags'] . ", source:{$this->shopDomain}" : "source:{$this->shopDomain}";

        try {
            if ($this->isUpdate) {
                $response = $targetShop->api()->rest('PUT', '/admin/api/2023-04/products/' . $product['id'] . '.json', ['product' => $product]);
            } else {
                $response = $targetShop->api()->rest('POST', '/admin/api/2023-04/products.json', ['product' => $product]);
            }
            \Log::info('Product synced to target shop', [
                'product_id' => $product['id'],
                'target_shop' => $this->targetShopDomain,
                'response_status' => $response['status'],
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error syncing product', [
                'error' => $e->getMessage(),
                'product_id' => $product['id'],
                'target_shop' => $this->targetShopDomain,
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);
        }
    }
}