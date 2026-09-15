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
        Schema::table('coin_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('coin_packages', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable()->after('price');
            }
            if (!Schema::hasColumn('coin_packages', 'currency_symbol')) {
                $table->string('currency_symbol', 10)->default('$')->after('country_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coin_packages', function (Blueprint $table) {
            if (Schema::hasColumn('coin_packages', 'country_id')) {
                $table->dropColumn('country_id');
            }
            if (Schema::hasColumn('coin_packages', 'currency_symbol')) {
                $table->dropColumn('currency_symbol');
            }
        });
    }
};
