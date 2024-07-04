<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Messaging\Jobs\AppUninstalledJob as ShopifyAppUninstalledJob;
use App\Models\StoreConnection;
use App\Models\User;
use Osiset\ShopifyApp\Contracts\Commands\Shop as ShopCommand;
use Osiset\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use Osiset\ShopifyApp\Actions\CancelCurrentPlan;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

class AppUninstalledJob extends ShopifyAppUninstalledJob
{
    public function handle(ShopCommand $shopCommand, ShopQuery $shopQuery, CancelCurrentPlan $cancelCurrentPlanAction): bool
    {
        Log::info('AppUninstalledJob handle method started');
        
        try {
            // Get the shop domain
            $shopDomainString = $this->getDomain();
            Log::info("Shop domain retrieved", ['shopDomain' => $shopDomainString]);

            if (empty($shopDomainString)) {
                Log::error("No shop domain found for uninstall process");
                return false;
            }

            Log::info("App uninstall process started for shop: {$shopDomainString}");

            // Find and delete the store connection
            $storeConnection = StoreConnection::where('shop_domain', $shopDomainString)->first();
            if ($storeConnection) {
                // Detach all connected stores
                $storeConnection->connectedStores()->detach();
                $storeConnection->delete();
                Log::info("Store connection deleted for shop: {$shopDomainString}");
            } else {
                Log::info("No store connection found for shop: {$shopDomainString}");
            }

            // Find and delete the user (shop)
            $user = User::where('name', $shopDomainString)->first();
            if ($user) {
                $user->delete();
                Log::info("User deleted for shop: {$shopDomainString}");
            } else {
                Log::warning("User not found for shop: {$shopDomainString}");
            }

            Log::info("App uninstall process completed successfully for shop: {$shopDomainString}");

            return true;
        } catch (\Exception $e) {
            Log::error("Error during app uninstall process", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    protected function getDomain(): ?string
    {
        return $this->domain ?? null;
    }
}