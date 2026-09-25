<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('wallet_tab_settings')) {
            Schema::create('wallet_tab_settings', function (Blueprint $table) {
                $table->id();
                $table->string('tab_key')->unique();
                $table->string('tab_name');
                $table->string('sub_title')->nullable();
                $table->string('icon_class')->nullable();
                $table->tinyInteger('status')->default(1)->comment('1: Show/Visible, 0: Hide');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            // Seed default 3 tabs
            DB::table('wallet_tab_settings')->insert([
                [
                    'tab_key'    => 'exchange',
                    'tab_name'   => 'Exchange',
                    'sub_title'  => 'Convert coins',
                    'icon_class' => 'fas fa-exchange-alt',
                    'status'     => 1,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tab_key'    => 'transfer',
                    'tab_name'   => 'Transfer',
                    'sub_title'  => 'Send money',
                    'icon_class' => 'fas fa-paper-plane',
                    'status'     => 1,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tab_key'    => 'withdraw',
                    'tab_name'   => 'Withdraw',
                    'sub_title'  => 'Bank withdrawal',
                    'icon_class' => 'fas fa-university',
                    'status'     => 1,
                    'sort_order' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_tab_settings');
    }
};
