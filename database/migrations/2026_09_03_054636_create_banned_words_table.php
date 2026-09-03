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
        Schema::create('banned_words', function (Blueprint $table) {
            $table->id();
            $table->string('word');
            $table->string('category')->nullable()->default('general');
            $table->tinyInteger('status')->default(1)->comment('1=active, 0=disabled');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_words');
    }
};
