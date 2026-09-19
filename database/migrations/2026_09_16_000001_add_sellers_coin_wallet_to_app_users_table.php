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
        if (!Schema::hasColumn('app_users', 'sellers_coin_wallet')) {
            Schema::table('app_users', function (Blueprint $table) {
                $table->unsignedBigInteger('sellers_coin_wallet')->default(0)->after('buy_coins_wallet');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('app_users', 'sellers_coin_wallet')) {
            Schema::table('app_users', function (Blueprint $table) {
                $table->dropColumn('sellers_coin_wallet');
            });
        }
    }
};
