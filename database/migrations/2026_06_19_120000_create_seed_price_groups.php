<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seed_price_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->decimal('price', 12, 4)->nullable();
            $table->string('merit_code', 20);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $codes = [
            '1', '2', '3', '4', '5', '6',
            'A', 'B', 'C', 'D', 'E', 'F', 'G',
            'BALTI', 'TALUAED', 'LINT',
            'EX1', 'EX2', 'EX3',
        ];

        DB::table('seed_price_groups')->insert(
            collect($codes)->map(fn (string $code, int $index) => [
                'code'       => $code,
                'name'       => match ($code) {
                    'BALTI'   => 'Balti',
                    'TALUAED' => 'Taluaed',
                    'LINT'    => 'Lint',
                    default   => 'Grupp '.$code,
                },
                'price'      => null,
                'merit_code' => $code,
                'is_active'  => true,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('seed_price_group_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('seed_price_groups')
                ->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('seed_price_group_id')
                ->nullable()
                ->after('product_id')
                ->constrained('seed_price_groups')
                ->nullOnDelete();
            $table->string('seed_price_group_code', 20)->nullable();
            $table->string('seed_price_group_name', 100)->nullable();
            $table->string('seed_price_group_merit_code', 20)->nullable();
            $table->decimal('seed_price_group_price', 12, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seed_price_group_id');
            $table->dropColumn([
                'seed_price_group_code',
                'seed_price_group_name',
                'seed_price_group_merit_code',
                'seed_price_group_price',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seed_price_group_id');
        });

        Schema::dropIfExists('seed_price_groups');
    }
};
