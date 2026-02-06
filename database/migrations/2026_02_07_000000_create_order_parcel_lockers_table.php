<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_parcel_lockers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->string('carrier')->nullable(); // omniva, smartpost
            $table->string('locker_id')->nullable();
            $table->string('locker_name')->nullable();
            $table->string('locker_address')->nullable();
            $table->string('locker_city')->nullable();
            $table->string('locker_postcode')->nullable();
            $table->string('locker_country')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_parcel_lockers');
    }
};
