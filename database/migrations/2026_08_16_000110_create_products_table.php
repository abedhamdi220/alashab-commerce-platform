<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->unsignedTinyInteger('discount_percentage')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_discreet')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->integer('stock_quantity')->nullable();
            $table->string('origin')->nullable();
            $table->string('extraction_method')->nullable();
            $table->text('ingredients')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['merchant_id', 'is_active']);
            $table->index(['merchant_id', 'category_id']);
            $table->index(['merchant_id', 'is_bestseller']);
            $table->index(['merchant_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
