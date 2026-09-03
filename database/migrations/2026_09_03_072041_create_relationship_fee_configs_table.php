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
        Schema::create('relationship_fee_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('country_id')->nullable()->comment('Null means All Countries');
            $table->string('relationship_type');
            $table->unsignedBigInteger('invite_fee')->default(0);
            $table->unsignedBigInteger('break_fee')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Disabled');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_fee_configs');
    }
};
