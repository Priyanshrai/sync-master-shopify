<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncedItem extends Model
{
    protected $fillable = [
        'item_type',
        'item_id',
        'source_shop_domain',
        'target_shop_domain',
    ];
}