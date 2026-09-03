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
        Schema::table('exchange_histories', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->unique()->after('coins_received');
            $table->string('wallet_type')->nullable()->default('main')->after('transaction_id');
            $table->string('status')->default('success')->after('wallet_type');
        });
    }

    public function down(): void
    {
        Schema::table('exchange_histories', function (Blueprint $table) {
            $table->dropColumn(['transaction_id', 'wallet_type', 'status']);
        });
    }
};
