<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashiers', function (Blueprint $table) {
            if (! Schema::hasColumn('cashiers', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('password');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'cashier_sync_token')) {
                $table->string('cashier_sync_token', 26)->nullable()->unique()->after('team_role_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cashiers', function (Blueprint $table) {
            if (Schema::hasColumn('cashiers', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cashier_sync_token')) {
                $table->dropColumn('cashier_sync_token');
            }
        });
    }
};
