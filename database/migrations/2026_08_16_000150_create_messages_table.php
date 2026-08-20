<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('platform', 32);
            $table->enum('direction', ['inbound', 'outbound'])->index();
            $table->string('message_type')->default('text');
            $table->text('body')->nullable();
            $table->string('platform_message_id')->nullable();
            $table->timestamps();

            $table->unique(
                ['merchant_id', 'platform', 'platform_message_id'],
                'messages_merchant_platform_message_unique'
            );
            $table->index(['merchant_id', 'customer_id', 'created_at']);
            $table->index(['merchant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
