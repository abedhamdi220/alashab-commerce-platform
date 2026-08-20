<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('platform', 32);
            $table->string('platform_sender_id');
            $table->string('phone_number')->nullable();
            $table->timestamps();

            $table->unique(
                ['merchant_id', 'platform', 'platform_sender_id'],
                'customers_merchant_platform_sender_unique'
            );
            $table->index(['merchant_id', 'phone_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
