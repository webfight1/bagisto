<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dpd_locations', function (Blueprint $table) {
            $table->increments('id');

            $table->string('parcel_shop_id')->unique();
            $table->string('legacy_shop_id')->nullable();
            $table->string('parcel_shop_type')->index();
            $table->string('company_name');
            $table->string('company_short_name')->nullable();

            $table->string('street')->nullable();
            $table->string('house_no')->nullable();
            $table->string('address_line2')->nullable();

            $table->string('country_code', 2)->index();
            $table->string('zip_code')->nullable();
            $table->string('city')->nullable();

            $table->decimal('longitude', 10, 6)->nullable();
            $table->decimal('latitude', 10, 6)->nullable();

            $table->json('opening_hours')->nullable();
            $table->timestamp('source_modified_at')->nullable();
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->index(['country_code', 'city']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('dpd_locations');
    }
};
