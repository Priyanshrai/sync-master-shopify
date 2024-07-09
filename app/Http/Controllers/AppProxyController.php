<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppProxyController extends Controller
{
    public function getConnectionId(Request $request)
    {
        // In a real scenario, you'd fetch this from your database
        // or generate it based on the authenticated shop
        $connectionId = md5($request->get('shop') . time());
        
        return response($connectionId, 200)->header('Content-Type', 'text/plain');
    }
}