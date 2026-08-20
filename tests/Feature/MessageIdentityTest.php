<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class MessageIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_message_identity_is_scoped_to_platform_and_merchant(): void
    {
        $merchant = User::factory()->create(['store_slug' => 'merchant-one']);

        $whatsAppCustomer = $this->customer($merchant, 'whatsapp', 'wa-1');
        $messengerCustomer = $this->customer($merchant, 'messenger', 'ms-1');

        $this->createMessage($merchant, $whatsAppCustomer, 'whatsapp', 'shared-external-id');
        $this->createMessage($merchant, $messengerCustomer, 'messenger', 'shared-external-id');

        $this->assertDatabaseCount('messages', 2);

        $this->expectException(QueryException::class);

        $this->createMessage($merchant, $whatsAppCustomer, 'whatsapp', 'shared-external-id');
    }

    private function customer(User $merchant, string $platform, string $senderId): Customer
    {
        $customer = new Customer([
            'platform' => $platform,
            'platform_sender_id' => $senderId,
            'name' => 'Test Customer',
        ]);
        $customer->merchant_id = $merchant->id;
        $customer->save();

        return $customer;
    }

    private function createMessage(User $merchant, Customer $customer, string $platform, string $externalId): void
    {
        $message = new Message([
            'customer_id' => $customer->id,
            'platform' => $platform,
            'direction' => 'inbound',
            'message_type' => 'text',
            'body' => 'Test message',
            'platform_message_id' => $externalId,
        ]);
        $message->merchant_id = $merchant->id;
        $message->save();
    }
}
