<?php

namespace Tests\Feature;

use App\Jobs\ProcessMetaMessageJob;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundWebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_whatsapp_batch_processes_every_message_and_is_idempotent(): void
    {
        $merchant = User::factory()->create([
            'meta_phone_id' => '1234567890',
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => $merchant->meta_phone_id],
                        'contacts' => [
                            ['wa_id' => '201000000001', 'profile' => ['name' => 'First customer']],
                            ['wa_id' => '201000000002', 'profile' => ['name' => 'Second customer']],
                        ],
                        'messages' => [
                            [
                                'from' => '201000000001',
                                'id' => 'wamid.first',
                                'type' => 'text',
                                'text' => ['body' => 'First message'],
                            ],
                            [
                                'from' => '201000000002',
                                'id' => 'wamid.second',
                                'type' => 'text',
                                'text' => ['body' => 'Second message'],
                            ],
                        ],
                    ],
                ]],
            ]],
        ];

        ProcessMetaMessageJob::dispatchSync($payload);
        ProcessMetaMessageJob::dispatchSync($payload);

        $this->assertSame(2, Customer::forMerchant($merchant->id)->count());
        $this->assertSame(2, Message::forMerchant($merchant->id)->count());
        $this->assertDatabaseHas('messages', [
            'merchant_id' => $merchant->id,
            'platform_message_id' => 'wamid.first',
            'body' => 'First message',
        ]);
        $this->assertDatabaseHas('messages', [
            'merchant_id' => $merchant->id,
            'platform_message_id' => 'wamid.second',
            'body' => 'Second message',
        ]);
    }

    public function test_the_same_sender_on_different_platforms_creates_two_customer_identities(): void
    {
        $merchant = User::factory()->create();

        Customer::withoutGlobalScopes()->create([
            'merchant_id' => $merchant->id,
            'platform' => 'whatsapp',
            'platform_sender_id' => 'same-external-id',
            'name' => 'WhatsApp customer',
        ]);

        Customer::withoutGlobalScopes()->create([
            'merchant_id' => $merchant->id,
            'platform' => 'messenger',
            'platform_sender_id' => 'same-external-id',
            'name' => 'Messenger customer',
        ]);

        $this->assertSame(2, Customer::forMerchant($merchant->id)->count());
    }
}
