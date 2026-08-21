<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('game_session_id')
                ->nullable()
                ->constrained('game_sessions')
                ->nullOnDelete();

            $table->foreignId('game_session_player_id')
                ->nullable()
                ->constrained('game_session_players')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('app_users')
                ->nullOnDelete();

            // Vani UID
            $table->string('uid', 255)->nullable();

            /*
            SUD transaction/order information
            */
            $table->string('sud_order_id', 255)->nullable();

            $table->string('sud_out_order_id', 255)->nullable();

            $table->string('sud_notify_id', 255)->nullable();

            /*
            entry_fee
            win
            refund
            loss
            adjustment
            */
            $table->string('transaction_type', 50);

            /*
            Positive/negative coin movement
            */
            $table->bigInteger('amount')->default(0);

            /*
            Balance audit
            */
            $table->bigInteger('before_balance')->default(0);

            $table->bigInteger('after_balance')->default(0);

            $table->string('description')->nullable();

            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'reversed'
            ])->default('completed');

            /*
            Complete SUD callback/request
            */
            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('uid');
            $table->index('sud_order_id');
            $table->index('sud_notify_id');
            $table->index('transaction_type');

            /*
            Important for duplicate settlement protection.
            */
            $table->unique([
                'sud_order_id',
                'transaction_type'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_transactions');
    }
};
