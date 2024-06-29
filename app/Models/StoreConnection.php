<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreConnection extends Model
{
    use HasFactory;

    protected $fillable = ['shop_domain', 'connection_id', 'connected_to'];
}