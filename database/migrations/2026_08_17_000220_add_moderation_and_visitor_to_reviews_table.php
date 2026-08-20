<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->foreignId('store_visitor_id')->nullable()->constrained('store_visitors')->nullOnDelete();
            $table->string('status', 16)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->index(['merchant_id', 'status', 'created_at']);
        });

        DB::table('reviews')->where('is_approved', true)->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex(['merchant_id', 'status', 'created_at']);
            $table->dropForeign(['store_visitor_id']);
            $table->dropForeign(['moderated_by']);
            $table->dropColumn([
                'store_visitor_id',
                'status',
                'rejection_reason',
                'moderated_by',
                'moderated_at',
            ]);
        });
    }
};
