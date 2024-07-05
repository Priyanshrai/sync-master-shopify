<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SyncOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $shopDomain;
    public $targetShopDomain;
    public $orderData;
    public $isUpdate;

    public function __construct($shopDomain, $orderData, $isUpdate = false)
    {
        $this->shopDomain = $shopDomain;
        $this->orderData = is_string($orderData) ? json_decode($orderData, true) : $orderData;
        $this->isUpdate = $isUpdate;
    }

    public function handle()
    {
        $targetShop = User::where('name', $this->targetShopDomain)->first();
        
        if (!$targetShop) {
            \Log::error("Target shop not found: {$this->targetShopDomain}");
            return;
        }

        $order = $this->orderData;
        $order['tags'] = isset($order['tags']) ? $order['tags'] . ", source:{$this->shopDomain}" : "source:{$this->shopDomain}";

        try {
            if ($this->isUpdate) {
                $response = $targetShop->api()->rest('PUT', '/admin/api/2023-04/orders/' . $order['id'] . '.json', ['order' => $order]);
            } else {
                // For orders, we need to create a draft order first
                $draftOrderData = $this->prepareDraftOrderData($order);
                $response = $targetShop->api()->rest('POST', '/admin/api/2023-04/draft_orders.json', ['draft_order' => $draftOrderData]);
                
                // Complete the draft order
                if ($response['status'] === 201) {
                    $draftOrderId = $response['body']['draft_order']['id'];
                    $completeResponse = $targetShop->api()->rest('POST', "/admin/api/2023-04/draft_orders/{$draftOrderId}/complete.json");
                    $response = $completeResponse; // Update response for logging
                }
            }
            \Log::info('Order synced to target shop', [
                'order_id' => $order['id'],
                'target_shop' => $this->targetShopDomain,
                'response_status' => $response['status'],
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error syncing order', [
                'error' => $e->getMessage(),
                'order_id' => $order['id'],
                'target_shop' => $this->targetShopDomain,
                'action' => $this->isUpdate ? 'update' : 'create'
            ]);
        }
    }

    protected function prepareDraftOrderData($order)
    {
        // Prepare draft order data from the original order
        $draftOrderData = [
            'email' => $order['email'],
            'line_items' => $order['line_items'],
            'shipping_address' => $order['shipping_address'] ?? null,
            'billing_address' => $order['billing_address'] ?? null,
            'note' => $order['note'] ?? null,
            'tags' => $order['tags'] ?? null,
            'metafields' => $order['metafields'] ?? null,
        ];

        // Add any other necessary fields

        return $draftOrderData;
    }
}