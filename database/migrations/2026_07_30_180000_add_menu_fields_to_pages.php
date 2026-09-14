<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('in_menu')
                ->default(false)
                ->after('status');

            $table->unsignedSmallInteger('menu_position')
                ->nullable()
                ->after('in_menu');

            // Optional short label (e.g. "KKK" while page title is "Korduma kippuvad küsimused")
            $table->string('menu_label')
                ->nullable()
                ->after('menu_position');

            $table->index(['in_menu', 'menu_position'], 'pages_menu_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('pages_menu_idx');
            $table->dropColumn(['in_menu', 'menu_position', 'menu_label']);
        });
    }
};
