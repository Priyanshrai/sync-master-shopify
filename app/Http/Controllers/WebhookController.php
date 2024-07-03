<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\AppUninstalledJob;

class WebhookController extends Controller
{
    public function handleAppUninstalled(Request $request)
    {
        // Verify the webhook if necessary

        // Dispatch the job to handle the app uninstallation
        AppUninstalledJob::dispatch($request->input('domain'));

        return response()->json(['message' => 'Webhook received successfully'], 200);
    }
}