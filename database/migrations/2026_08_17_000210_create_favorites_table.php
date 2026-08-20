<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('store_visitor_id')->constrained('store_visitors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['merchant_id', 'product_id', 'store_visitor_id']);
            $table->index(['merchant_id', 'product_id', 'created_at']);
            $table->index(['merchant_id', 'store_visitor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
