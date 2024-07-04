<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class SyncStoreData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sourceShopDomain;
    protected $targetShopDomain;

    public function __construct($sourceShopDomain, $targetShopDomain)
    {
        $this->sourceShopDomain = $sourceShopDomain;
        $this->targetShopDomain = $targetShopDomain;
    }

    public function handle()
    {
        Log::info('Starting SyncStoreData job', [
            'source_shop' => $this->sourceShopDomain,
            'target_shop' => $this->targetShopDomain
        ]);

        $sourceShop = User::where('name', $this->sourceShopDomain)->first();
        $targetShop = User::where('name', $this->targetShopDomain)->first();

        if (!$sourceShop || !$targetShop) {
            Log::error('One or both shops not found', [
                'source_shop' => $this->sourceShopDomain,
                'target_shop' => $this->targetShopDomain
            ]);
            return;
        }

        $this->syncProducts($sourceShop, $targetShop);
        $this->syncCustomers($sourceShop, $targetShop);
        $this->syncOrders($sourceShop, $targetShop);

        Log::info('Completed SyncStoreData job', [
            'source_shop' => $this->sourceShopDomain,
            'target_shop' => $this->targetShopDomain
        ]);
    }

    protected function syncProducts($sourceShop, $targetShop)
    {
        Log::info('Starting product sync', [
            'source_shop' => $sourceShop->name,
            'target_shop' => $targetShop->name
        ]);

        try {
            $response = $sourceShop->api()->rest('GET', '/admin/products.json');
            Log::info('API response for products', ['response' => $response]);

            $products = $response['body']->products ?? [];
            Log::info('Retrieved products from source shop', ['count' => count($products)]);

            foreach ($products as $product) {
                $product['tags'] = isset($product['tags']) ? $product['tags'] . ", source:{$sourceShop->name}" : "source:{$sourceShop->name}";
                $response = $targetShop->api()->rest('POST', '/admin/products.json', ['product' => $product]);
                Log::info('Synced product to target shop', [
                    'product_id' => $product['id'],
                    'response_status' => $response['status']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing products', [
                'error' => $e->getMessage(),
                'source_shop' => $sourceShop->name,
                'target_shop' => $targetShop->name
            ]);
        }

        Log::info('Completed product sync', [
            'source_shop' => $sourceShop->name,
            'target_shop' => $targetShop->name
        ]);
    }

    protected function syncCustomers($sourceShop, $targetShop)
    {
        Log::info('Starting customer sync', [
            'source_shop' => $sourceShop->name,
            'target_shop' => $targetShop->name
        ]);

        try {
            $response = $sourceShop->api()->rest('GET', '/admin/customers.json');
            Log::info('API response for customers', ['response' => $response]);

            $customers = $response['body']->customers ?? [];
            Log::info('Retrieved customers from source shop', ['count' => count($customers)]);

            foreach ($customers as $customer) {
                $customer['tags'] = isset($customer['tags']) ? $customer['tags'] . ", source:{$sourceShop->name}" : "source:{$sourceShop->name}";
                $response = $targetShop->api()->rest('POST', '/admin/customers.json', ['customer' => $customer]);
                Log::info('Synced customer to target shop', [
                    'customer_id' => $customer['id'],
                    'response_status' => $response['status']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing customers', [
                'error' => $e->getMessage(),
                'source_shop' => $sourceShop->name,
                'target_shop' => $targetShop->name
            ]);
        }

        Log::info('Completed customer sync', [
            'source_shop' => $sourceShop->name,
            'target_shop' => $targetShop->name
        ]);
    }

    protected function syncOrders($sourceShop, $targetShop)
    {
        Log::info('Starting order sync', [
            'source_shop' => $sourceShop->name,
            'target_shop' => $targetShop->name
        ]);

        try {
            $response = $sourceShop->api()->rest('GET', '/admin/orders.json');
            Log::info('API response for orders', ['response' => $response]);

            $orders = $response['body']->orders ?? [];
            Log::info('Retrieved orders from source shop', ['count' => count($orders)]);

            foreach ($orders as $order) {
                $order['tags'] = isset($order['tags']) ? $order['tags'] . ", source:{$sourceShop->name}" : "source:{$sourceShop->name}";
                $response = $targetShop->api()->rest('POST', '/admin/orders.json', ['order' => $order]);
                Log::info('Synced order to target shop', [
                    'order_id' => $order['id'],
                    'response_status' => $response['status']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing orders', [
                'error' => $e->getMessage(),
                'source_shop' => $sourceShop->name,
                'target_shop' => $targetShop->name
            ]);
        }

        Log::info('Completed order sync', [
            'source_shop' => $sourceShop->name,
            'target_shop' => $targetShop->name
        ]);
    }
}