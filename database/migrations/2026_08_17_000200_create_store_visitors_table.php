<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_visitors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('visitor_identifier');
            $table->string('display_name', 100)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['merchant_id', 'visitor_identifier']);
            $table->index(['merchant_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_visitors');
    }
};
