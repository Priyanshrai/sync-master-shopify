<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Messaging\Jobs\AppUninstalledJob as ShopifyAppUninstalledJob;
use App\Models\StoreConnection;
use App\Models\User;

class AppUninstalledJob extends ShopifyAppUninstalledJob
{
    public function handle()
    {
        // Call the parent handle method to perform default uninstall actions
        parent::handle();

        // Get the shop domain
        $shopDomain = $this->shopDomain;

        Log::info("App uninstall process started for shop: {$shopDomain}");

        try {
            // Find and delete the store connection
            $storeConnection = StoreConnection::where('shop_domain', $shopDomain)->first();
            if ($storeConnection) {
                // Detach all connected stores
                $storeConnection->connectedStores()->detach();
                $storeConnection->delete();
                Log::info("Store connection deleted for shop: {$shopDomain}");
            }

            // Find and delete the user (shop)
            $user = User::where('name', $shopDomain)->first();
            if ($user) {
                $user->delete();
                Log::info("User deleted for shop: {$shopDomain}");
            }

            Log::info("App uninstall process completed successfully for shop: {$shopDomain}");
        } catch (\Exception $e) {
            Log::error("Error during app uninstall process for shop: {$shopDomain}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}