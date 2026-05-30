<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('cashier_id')->constrained('cashiers')->cascadeOnDelete();
            $table->string('local_id', 80);
            $table->string('cpf', 20)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('payment_method', 40)->nullable();
            $table->json('payment_payload')->nullable();
            $table->json('items');
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'cashier_id', 'local_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_sales');
    }
};
