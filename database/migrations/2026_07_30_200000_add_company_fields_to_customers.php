<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('company_name', 255)->nullable()->after('last_name');
            $table->string('company_reg', 255)->nullable()->after('company_name');   // Äriregistri kood
            $table->string('vat_id', 255)->nullable()->after('company_reg');          // KMKR nr
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'company_reg', 'vat_id']);
        });
    }
};
