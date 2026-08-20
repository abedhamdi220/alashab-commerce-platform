<?php

namespace Tests\Feature;

use App\Events\OrderPrepared;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderPreparedAfterCommitTest extends TestCase
{
    use DatabaseMigrations;

    public function test_prepared_event_is_dispatched_after_the_order_transaction_commits(): void
    {
        Event::fake([OrderPrepared::class]);

        $merchant = User::factory()->create(['store_slug' => 'merchant-order']);
        $this->actingAs($merchant);

        $customer = new Customer([
            'platform' => 'whatsapp',
            'platform_sender_id' => 'sender-1',
            'name' => 'Customer',
        ]);
        $customer->merchant_id = $merchant->id;
        $customer->save();

        $order = new Order([
            'customer_id' => $customer->id,
            'total_price' => '0.00',
            'status' => 'confirmed',
        ]);
        $order->merchant_id = $merchant->id;
        $order->save();

        app(OrderService::class)->transition($order, 'prepared');

        Event::assertDispatched(OrderPrepared::class, function (OrderPrepared $event) use ($order): bool {
            return $event->order->id === $order->id && $event->order->status === 'prepared';
        });
    }
}
