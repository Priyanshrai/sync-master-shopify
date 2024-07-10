<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncedItemsTable extends Migration
{
    public function up()
    {
        Schema::create('synced_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_type');
            $table->bigInteger('item_id');
            $table->string('source_shop_domain');
            $table->string('target_shop_domain');
            $table->timestamps();

            $table->unique(['item_type', 'item_id', 'source_shop_domain', 'target_shop_domain'], 'synced_items_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('synced_items');
    }
}