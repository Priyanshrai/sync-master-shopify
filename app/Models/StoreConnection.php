<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreConnection extends Model
{
    protected $fillable = ['shop_domain', 'connection_id'];

    public function connectedStores()
    {
        return $this->belongsToMany(StoreConnection::class, 'store_connections_pivot', 'store_connection_id', 'connected_shop_domain', 'id', 'shop_domain');
    }
}