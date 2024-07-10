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

class SyncOrderJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shopDomain;
    public $targetShopDomain;
    public $orderData;
    public $isUpdate;
    public $uniqueJobIdentifier;

    public function __construct($shopDomain, $targetShopDomain, $orderData, $isUpdate = false)
    {
        $this->shopDomain = $shopDomain;
        $this->targetShopDomain = $targetShopDomain;
        $this->orderData = is_string($orderData) ? json_decode($orderData, true) : $orderData;
        $this->isUpdate = $isUpdate;
        $this->uniqueJobIdentifier = 'sync_order_' . $this->orderData['id'] . '_' . $targetShopDomain;
    }

    public function uniqueId()
    {
        return $this->uniqueJobIdentifier;
    }

    public function handle()
    {
        Log::info("Starting SyncOrderJob", [
            'source_shop' => $this->shopDomain,
            'target_shop' => $this->targetShopDomain,
            'is_update' => $this->isUpdate,
            'order_data' => $this->orderData
        ]);

        if (empty($this->orderData) || !isset($this->orderData['id'])) {
            Log::error("Invalid order data", [
                'source_shop' => $this->shopDomain,
                'target_shop' => $this->targetShopDomain,
                'order_data' => $this->orderData
            ]);
            return;
        }

        $existingSyncedItem = SyncedItem::where('item_type', 'order')
            ->where('item_id', $this->orderData['id'])
            ->where('source_shop_domain', $this->shopDomain)
            ->where('target_shop_domain', $this->targetShopDomain)
            ->first();

        if ($existingSyncedItem && !$this->isUpdate) {
            Log::info('Order already synced', [
                'order_id' => $this->orderData['id'],
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

        $this->orderData['tags'] = $this->appendSourceTag($this->orderData['tags'] ?? '', $this->shopDomain);

        try {
            if ($this->isUpdate) {
                $response = $targetShop->api()->rest('PUT', '/admin/api/2023-04/orders/' . $this->orderData['id'] . '.json', ['order' => $this->orderData]);
            } else {
                $draftOrderData = $this->prepareDraftOrderData($this->orderData);
                $response = $targetShop->api()->rest('POST', '/admin/api/2023-04/draft_orders.json', ['draft_order' => $draftOrderData]);
                
                if ($response['status'] === 201) {
                    $draftOrderId = $response['body']['draft_order']['id'];
                    $completeResponse = $targetShop->api()->rest('POST', "/admin/api/2023-04/draft_orders/{$draftOrderId}/complete.json");
                    $response = $completeResponse;
                }
            }

            Log::info('Order synced to target shop', [
                'order_id' => $this->orderData['id'],
                'target_shop' => $this->targetShopDomain,
                'response_status' => $response['status'],
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);

            SyncedItem::updateOrCreate(
                [
                    'item_type' => 'order',
                    'item_id' => $this->orderData['id'],
                    'source_shop_domain' => $this->shopDomain,
                    'target_shop_domain' => $this->targetShopDomain
                ],
                ['last_synced_at' => now()]
            );
        } catch (\Exception $e) {
            Log::error('Error syncing order', [
                'error' => $e->getMessage(),
                'order_id' => $this->orderData['id'],
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

    protected function prepareDraftOrderData($order)
    {
        return [
            'email' => $order['email'],
            'line_items' => $order['line_items'],
            'shipping_address' => $order['shipping_address'] ?? null,
            'billing_address' => $order['billing_address'] ?? null,
            'note' => $order['note'] ?? null,
            'tags' => $order['tags'] ?? null,
            'metafields' => $order['metafields'] ?? null,
        ];
    }
}