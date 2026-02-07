<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esto_webhooks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reference')->unique();
            $table->unsignedInteger('order_id')->nullable();
            $table->string('status')->nullable();
            $table->decimal('amount', 12, 4)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->index('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esto_webhooks');
    }
};
