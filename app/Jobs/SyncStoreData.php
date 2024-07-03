<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kyon147\ShopifyApp\Facades\ShopifyApp;
use Illuminate\Support\Facades\Log;

class SyncStoreData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sourceShop;
    protected $targetShop;

    public function __construct($sourceShop, $targetShop)
    {
        $this->sourceShop = $sourceShop;
        $this->targetShop = $targetShop;
    }

    public function handle()
    {
        Log::info('Starting SyncStoreData job', [
            'source_shop' => $this->sourceShop->getDomain()->toNative(),
            'target_shop' => $this->targetShop->getDomain()->toNative()
        ]);

        $this->syncProducts();
        $this->syncCustomers();
        $this->syncOrders();

        Log::info('Completed SyncStoreData job', [
            'source_shop' => $this->sourceShop->getDomain()->toNative(),
            'target_shop' => $this->targetShop->getDomain()->toNative()
        ]);
    }

    protected function syncProducts()
    {
        Log::info('Starting product sync', [
            'source_shop' => $this->sourceShop->getDomain()->toNative(),
            'target_shop' => $this->targetShop->getDomain()->toNative()
        ]);

        $products = $this->sourceShop->api()->rest('GET', '/admin/products.json')['body']['products'];
        Log::info('Retrieved products from source shop', ['count' => count($products)]);

        foreach ($products as $product) {
            $product['tags'] .= ", source:{$this->sourceShop->getDomain()->toNative()}";
            $response = $this->targetShop->api()->rest('POST', '/admin/products.json', ['product' => $product]);
            Log::info('Synced product to target shop', [
                'product_id' => $product['id'],
                'response_status' => $response['status']
            ]);
        }

        Log::info('Completed product sync', [
            'source_shop' => $this->sourceShop->getDomain()->toNative(),
            'target_shop' => $this->targetShop->getDomain()->toNative()
        ]);
    }

    protected function syncCustomers()
    {
        Log::info('Starting customer sync', [
            'source_shop' => $this->sourceShop->getDomain()->toNative(),
            'target_shop' => $this->targetShop->getDomain()->toNative()
        ]);

        $customers = $this->sourceShop->api()->rest('GET', '/admin/customers.json')['body']['customers'];
        Log::info('Retrieved customers from source shop', ['count' => count($customers)]);

        foreach ($customers as $customer) {
            $customer['tags'] .= ", source:{$this->sourceShop->getDomain()->toNative()}";
            $response = $this->targetShop->api()->rest('POST', '/admin/customers.json', ['customer' => $customer]);
            Log::info('Synced customer to target shop', [
                'customer_id' => $customer['id'],
                'response_status' => $response['status']
            ]);
        }

        Log::info('Completed customer sync', [
            'source_shop' => $this->sourceShop->getDomain()->toNative(),
            'target_shop' => $this->targetShop->getDomain()->toNative()
        ]);
    }

    protected function syncOrders()
    {
        Log::info('Starting order sync', [
            'source_shop' => $this->sourceShop->getDomain()->toNative(),
            'target_shop' => $this->targetShop->getDomain()->toNative()
        ]);

        $orders = $this->sourceShop->api()->rest('GET', '/admin/orders.json')['body']['orders'];
        Log::info('Retrieved orders from source shop', ['count' => count($orders)]);

        foreach ($orders as $order) {
            $order['tags'] .= ", source:{$this->sourceShop->getDomain()->toNative()}";
            $response = $this->targetShop->api()->rest('POST', '/admin/orders.json', ['order' => $order]);
            Log::info('Synced order to target shop', [
                'order_id' => $order['id'],
                'response_status' => $response['status']
            ]);
        }

        Log::info('Completed order sync', [
            'source_shop' => $this->sourceShop->getDomain()->toNative(),
            'target_shop' => $this->targetShop->getDomain()->toNative()
        ]);
    }
}