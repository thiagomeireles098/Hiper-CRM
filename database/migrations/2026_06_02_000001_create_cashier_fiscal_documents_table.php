<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_fiscal_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('cashier_id')->constrained('cashiers')->cascadeOnDelete();
            $table->unsignedBigInteger('cashier_sale_id')->nullable()->index();
            $table->string('local_id', 80);
            $table->string('type', 20)->default('nfce');
            $table->string('status', 30)->default('pending')->index();
            $table->string('provider', 40)->nullable();
            $table->string('provider_document_id')->nullable();
            $table->string('access_key', 80)->nullable();
            $table->string('series', 20)->nullable();
            $table->string('number', 40)->nullable();
            $table->string('xml_path')->nullable();
            $table->string('danfe_url')->nullable();
            $table->json('print_payload')->nullable();
            $table->json('authorization_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'cashier_id', 'local_id', 'type'], 'cashier_fiscal_local_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_fiscal_documents');
    }
};
