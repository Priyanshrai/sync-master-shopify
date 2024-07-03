<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreConnectionsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('store_connections')) {
            Schema::create('store_connections', function (Blueprint $table) {
                $table->id();
                $table->string('shop_domain');
                $table->string('connection_id')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('store_connections_pivot')) {
            Schema::create('store_connections_pivot', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_connection_id');
                $table->string('connected_shop_domain');
                $table->timestamps();
        
                $table->foreign('store_connection_id')
                      ->references('id')
                      ->on('store_connections')
                      ->onDelete('cascade');
        
                // Use a shorter name for the unique index
                $table->unique(['store_connection_id', 'connected_shop_domain'], 'unique_store_connection');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('store_connections_pivot');
        Schema::dropIfExists('store_connections');
    }
}