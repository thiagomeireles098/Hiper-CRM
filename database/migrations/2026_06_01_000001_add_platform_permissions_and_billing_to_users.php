<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'platform_permissions')) {
                $table->json('platform_permissions')->nullable()->after('cashier_sync_token');
            }
            if (! Schema::hasColumn('users', 'platform_subscription_config')) {
                $table->json('platform_subscription_config')->nullable()->after('platform_permissions');
            }
            if (! Schema::hasColumn('users', 'platform_payment_due_day')) {
                $table->unsignedTinyInteger('platform_payment_due_day')->nullable()->after('platform_subscription_config');
            }
            if (! Schema::hasColumn('users', 'platform_payment_paid')) {
                $table->boolean('platform_payment_paid')->default(false)->after('platform_payment_due_day');
            }
            if (! Schema::hasColumn('users', 'platform_payment_grace_days')) {
                $table->unsignedSmallInteger('platform_payment_grace_days')->default(0)->after('platform_payment_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $drop = array_values(array_filter([
                Schema::hasColumn('users', 'platform_payment_grace_days') ? 'platform_payment_grace_days' : null,
                Schema::hasColumn('users', 'platform_payment_paid') ? 'platform_payment_paid' : null,
                Schema::hasColumn('users', 'platform_payment_due_day') ? 'platform_payment_due_day' : null,
                Schema::hasColumn('users', 'platform_subscription_config') ? 'platform_subscription_config' : null,
                Schema::hasColumn('users', 'platform_permissions') ? 'platform_permissions' : null,
            ]));

            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
};
