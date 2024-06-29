<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreConnectionsTable extends Migration
{
    public function up()
    {
        Schema::create('store_connections', function (Blueprint $table) {
            $table->id();
            $table->string('shop_domain')->unique();
            $table->string('connection_id')->unique();
            $table->string('connected_to')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('store_connections');
    }
}