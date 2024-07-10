<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\SyncedItem;

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

        $nextPageUrl = null;

        do {
            try {
                $endpoint = $nextPageUrl ?? '/admin/api/2023-07/products.json';
                $response = $sourceShop->api()->rest('GET', $endpoint);
                Log::info('API response for products', ['response' => $response]);

                $products = $response['body']['products'] ?? [];
                Log::info('Retrieved products from source shop', ['count' => count($products)]);

                foreach ($products as $product) {
                    SyncProductJob::dispatch($sourceShop->name, $targetShop->name, $product);
                }

                $nextPageUrl = $this->getNextPageUrl($response['link']['next'] ?? null);

            } catch (\Exception $e) {
                Log::error('Error syncing products', [
                    'error' => $e->getMessage(),
                    'source_shop' => $sourceShop->name,
                    'target_shop' => $targetShop->name
                ]);
                break;
            }
        } while ($nextPageUrl);

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

        $nextPageUrl = null;

        do {
            try {
                $endpoint = $nextPageUrl ?? '/admin/api/2023-07/customers.json';
                $response = $sourceShop->api()->rest('GET', $endpoint);
                Log::info('API response for customers', ['response' => $response]);

                $customers = $response['body']['customers'] ?? [];
                Log::info('Retrieved customers from source shop', ['count' => count($customers)]);

                foreach ($customers as $customer) {
                    SyncCustomerJob::dispatch($sourceShop->name, $targetShop->name, $customer);
                }

                $nextPageUrl = $this->getNextPageUrl($response['link']['next'] ?? null);

            } catch (\Exception $e) {
                Log::error('Error syncing customers', [
                    'error' => $e->getMessage(),
                    'source_shop' => $sourceShop->name,
                    'target_shop' => $targetShop->name
                ]);
                break;
            }
        } while ($nextPageUrl);

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

        $nextPageUrl = null;

        do {
            try {
                $endpoint = $nextPageUrl ?? '/admin/api/2023-07/orders.json';
                $response = $sourceShop->api()->rest('GET', $endpoint);
                Log::info('API response for orders', ['response' => $response]);

                $orders = $response['body']['orders'] ?? [];
                Log::info('Retrieved orders from source shop', ['count' => count($orders)]);

                foreach ($orders as $order) {
                    SyncOrderJob::dispatch($sourceShop->name, $targetShop->name, $order);
                }

                $nextPageUrl = $this->getNextPageUrl($response['link']['next'] ?? null);

            } catch (\Exception $e) {
                Log::error('Error syncing orders', [
                    'error' => $e->getMessage(),
                    'source_shop' => $sourceShop->name,
                    'target_shop' => $targetShop->name
                ]);
                break;
            }
        } while ($nextPageUrl);

        Log::info('Completed order sync', [
            'source_shop' => $sourceShop->name,
            'target_shop' => $targetShop->name
        ]);
    }

    private function getNextPageUrl($link)
    {
        if (!$link) {
            return null;
        }

        preg_match('/<(.*)>/', $link, $matches);
        return $matches[1] ?? null;
    }
}