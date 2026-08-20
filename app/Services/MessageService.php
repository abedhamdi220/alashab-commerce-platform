<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MessageService
{
    public function getUnifiedInbox(): LengthAwarePaginator
    {
        return Message::query()
            ->with([
                'customer' => function ($query): void {
                    $query->addSelect([
                        'active_order_id' => Order::query()
                            ->select('id')
                            ->whereColumn('customer_id', 'customers.id')
                            ->whereNotIn('status', ['shipped', 'cancelled'])
                            ->latest()
                            ->limit(1),
                    ]);
                },
                'media',
            ])
            ->latest('created_at')
            ->paginate(50)
            ->through(fn (Message $message): array => self::toPayload($message));
    }

    /** @return array<string, mixed> */
    public static function toPayload(Message $message): array
    {
        $message->loadMissing(['customer', 'media']);
        $customer = $message->customer;

        return [
            'id' => $message->id,
            'direction' => $message->direction,
            'message_type' => $message->message_type,
            'body' => $message->body,
            'platform' => $message->platform,
            'platform_message_id' => $message->platform_message_id,
            'created_at' => $message->created_at?->toDateTimeString(),
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'platform' => $customer->platform,
                'platform_sender_id' => $customer->platform_sender_id,
                'phone_number' => $customer->phone_number,
                'active_order_id' => $customer->active_order_id,
            ] : null,
            'media' => $message->getMedia('chat_media')->map(fn ($media): array => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'type' => $media->mime_type,
            ])->values()->all(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function sendReply(Customer $customer, string $body): ?array
    {
        /** @var User|null $merchant */
        $merchant = auth()->user();

        if (! $merchant || $merchant->id !== $customer->merchant_id) {
            Log::warning('Outbound message rejected because merchant context is invalid.', [
                'customer_id' => $customer->id,
            ]);

            return null;
        }

        $body = trim($body);
        if ($body === '') {
            return null;
        }

        $platformMessageId = 'local:'.Str::uuid();

        $sent = match ($customer->platform) {
            'whatsapp' => $this->sendWhatsAppMessage($merchant, $customer->platform_sender_id, $body),
            'messenger' => $this->sendMessengerMessage($merchant, $customer->platform_sender_id, $body),
            default => false,
        };

        if (! $sent) {
            return null;
        }

        $message = Message::create([
            'customer_id' => $customer->id,
            'platform' => $customer->platform,
            'direction' => 'outbound',
            'message_type' => 'text',
            'body' => $body,
            'platform_message_id' => $platformMessageId,
        ]);

        return self::toPayload($message);
    }

    private function sendWhatsAppMessage(User $merchant, string $to, string $body): bool
    {
        $baseUrl = rtrim((string) config('merchant_integrations.evolution.base_url'), '/');
        $apiKey = config('merchant_integrations.evolution.api_key');

        if ($baseUrl === '' || blank($apiKey) || blank($to)) {
            Log::warning('Evolution API is not configured for outbound WhatsApp.', ['merchant_id' => $merchant->id]);

            return false;
        }

        try {
            return Http::withHeaders(['apikey' => $apiKey])
                ->acceptJson()
                ->timeout(15)
                ->retry(2, 250)
                ->post("{$baseUrl}/message/sendText/merchant_{$merchant->id}", [
                    // Evolution API يتطلب رقماً دولياً رقمياً بلا رمز + أو JID.
                    'number' => preg_replace('/[^0-9]/', '', $to),
                    'text' => $body,
                    'delay' => 1200,
                ])
                ->successful();
        } catch (\Throwable $exception) {
            Log::error('Evolution outbound WhatsApp request failed.', [
                'merchant_id' => $merchant->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendMessengerMessage(User $merchant, string $to, string $body): bool
    {
        $token = $merchant->messenger_access_token ?: config('merchant_integrations.messenger.access_token');
        $pageId = $merchant->meta_page_id;

        if (blank($token) || blank($pageId) || blank($to)) {
            Log::warning('Messenger is not fully configured for outbound messaging.', ['merchant_id' => $merchant->id]);

            return false;
        }

        try {
            return Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->retry(2, 250)
                ->post("https://graph.facebook.com/v20.0/{$pageId}/messages", [
                    'recipient' => ['id' => $to],
                    'message' => ['text' => $body],
                ])
                ->successful();
        } catch (\Throwable $exception) {
            Log::error('Messenger outbound request failed.', [
                'merchant_id' => $merchant->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
