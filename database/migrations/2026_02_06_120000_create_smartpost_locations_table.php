<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('smartpost_locations', function (Blueprint $table) {
            $table->increments('id');

            $table->string('location_id')->unique();
            $table->string('postal_code')->index();
            $table->string('name');
            $table->string('country', 2)->index();
            $table->string('type')->index();

            $table->string('municipality')->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable();
            $table->string('house')->nullable();

            $table->decimal('lng', 10, 6)->nullable();
            $table->decimal('lat', 10, 6)->nullable();

            $table->timestamp('source_modified_at')->nullable();
            $table->json('raw')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('smartpost_locations');
    }
};
