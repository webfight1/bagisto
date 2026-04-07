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
        Schema::create('merit_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->string('merit_invoice_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('pending'); // pending, created, failed
            $table->json('merit_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->index('order_id');
            $table->index('merit_invoice_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merit_invoices');
    }
};
