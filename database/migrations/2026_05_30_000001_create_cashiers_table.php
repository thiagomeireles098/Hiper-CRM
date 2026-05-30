<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 120);
            $table->string('username', 120);
            $table->text('password');
            $table->timestamps();

            $table->unique(['tenant_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashiers');
    }
};
