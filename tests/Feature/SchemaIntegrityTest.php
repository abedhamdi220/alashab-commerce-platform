<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_tables_and_columns_exist_after_migrations(): void
    {
        foreach ([
            'users', 'categories', 'products', 'customers', 'messages', 'orders',
            'order_items', 'reviews', 'settings', 'personal_access_tokens',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing required table: {$table}");
        }

        foreach ([
            'store_slug', 'meta_phone_id', 'meta_page_id', 'delivery_driver_number',
            'whatsapp_access_token', 'messenger_access_token',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "Missing users column: {$column}");
        }

        $this->assertTrue(Schema::hasColumn('messages', 'platform'));
        $this->assertTrue(Schema::hasColumn('products', 'deleted_at'));
    }
}
