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
        Schema::create('cart_parcel_lockers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cart_id');
            $table->string('carrier')->nullable(); // omniva, smartpost
            $table->string('locker_id')->nullable();
            $table->string('locker_name')->nullable();
            $table->string('locker_address')->nullable();
            $table->string('locker_city')->nullable();
            $table->string('locker_postcode')->nullable();
            $table->string('locker_country')->nullable();
            $table->timestamps();

            $table->index('cart_id');
            $table->foreign('cart_id')->references('id')->on('cart')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_parcel_lockers');
    }
};
