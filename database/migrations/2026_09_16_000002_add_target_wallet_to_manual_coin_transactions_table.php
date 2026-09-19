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
        if (!Schema::hasColumn('manual_coin_transactions', 'target_wallet')) {
            Schema::table('manual_coin_transactions', function (Blueprint $table) {
                $table->string('target_wallet', 50)->nullable()->default('seller')->after('reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('manual_coin_transactions', 'target_wallet')) {
            Schema::table('manual_coin_transactions', function (Blueprint $table) {
                $table->dropColumn('target_wallet');
            });
        }
    }
};
