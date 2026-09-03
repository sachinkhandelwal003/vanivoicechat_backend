<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('user_permissions', 'actions')) {
            Schema::table('user_permissions', function (Blueprint $table) {
                $table->text('actions')->nullable()->after('allow_all');
            });
        }

        if (!Schema::hasColumn('role_permissions', 'actions')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->text('actions')->nullable()->after('allow_all');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_permissions', 'actions')) {
            Schema::table('user_permissions', function (Blueprint $table) {
                $table->dropColumn('actions');
            });
        }

        if (Schema::hasColumn('role_permissions', 'actions')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->dropColumn('actions');
            });
        }
    }
};
