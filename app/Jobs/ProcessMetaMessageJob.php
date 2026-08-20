<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMetaMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 90;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900, 1800];

    /** @param array<string, mixed> $payload */
    public function __construct(private readonly array $payload)
    {
    }

    public function handle(): void
    {
        try {
            if (($this->payload['platform'] ?? null) === 'whatsapp_evolution') {
                $this->processEvolution($this->payload);

                return;
            }

            foreach ($this->payload['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    if (is_array($value) && isset($value['messages'])) {
                        $this->processWhatsAppValue($value);
                    }
                }

                foreach ($entry['messaging'] ?? [] as $messaging) {
                    if (is_array($messaging)) {
                        $this->processMessengerMessage($messaging);
                    }
                }
            }
        } catch (Throwable $exception) {
            Log::error('Inbound webhook processing failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /** @param array<string, mixed> $value */
    private function processWhatsAppValue(array $value): void
    {
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        if (! is_string($phoneNumberId) || $phoneNumberId === '') {
            Log::warning('WhatsApp webhook ignored because metadata.phone_number_id is missing.');

            return;
        }

        $contactsByWaId = [];
        foreach ($value['contacts'] ?? [] as $contact) {
            if (is_array($contact) && isset($contact['wa_id'])) {
                $contactsByWaId[(string) $contact['wa_id']] = (string) data_get($contact, 'profile.name', 'عميل واتساب');
            }
        }

        $merchant = User::query()->where('meta_phone_id', $phoneNumberId)->first();
        if (! $merchant) {
            Log::warning('WhatsApp webhook ignored because no merchant owns the phone number ID.', [
                'phone_number_id' => $phoneNumberId,
            ]);

            return;
        }

        foreach ($value['messages'] ?? [] as $messageData) {
            if (! is_array($messageData)) {
                continue;
            }

            $senderId = $messageData['from'] ?? null;
            $messageId = $messageData['id'] ?? null;
            $messageType = $messageData['type'] ?? 'unsupported';

            if (! is_string($senderId) || ! is_string($messageId)) {
                Log::warning('WhatsApp webhook message ignored because its sender or message ID is missing.');
                continue;
            }

            $mediaId = null;
            $body = '';

            if ($messageType === 'text') {
                $body = (string) data_get($messageData, 'text.body', '');
            } elseif (in_array($messageType, ['image', 'audio', 'video', 'document'], true)) {
                $mediaId = data_get($messageData, "{$messageType}.id");
                $body = "مرفق: {$messageType}";
            } else {
                $body = "رسالة غير مدعومة: {$messageType}";
            }

            $this->saveInboundMessage(
                merchant: $merchant,
                platform: 'whatsapp',
                senderId: $senderId,
                messageId: $messageId,
                body: $body,
                customerName: $contactsByWaId[$senderId] ?? 'عميل واتساب',
                mediaId: is_string($mediaId) ? $mediaId : null,
            );
        }
    }

    /** @param array<string, mixed> $messaging */
    private function processMessengerMessage(array $messaging): void
    {
        if (! isset($messaging['message']) || (bool) data_get($messaging, 'message.is_echo', false)) {
            return;
        }

        $senderId = data_get($messaging, 'sender.id');
        $pageId = data_get($messaging, 'recipient.id');
        $messageId = data_get($messaging, 'message.mid');

        if (! is_string($senderId) || ! is_string($pageId) || ! is_string($messageId)) {
            Log::warning('Messenger webhook message ignored because its identity fields are incomplete.');

            return;
        }

        $merchant = User::query()->where('meta_page_id', $pageId)->first();
        if (! $merchant) {
            Log::warning('Messenger webhook ignored because no merchant owns the page ID.', ['page_id' => $pageId]);

            return;
        }

        $this->saveInboundMessage(
            merchant: $merchant,
            platform: 'messenger',
            senderId: $senderId,
            messageId: $messageId,
            body: (string) data_get($messaging, 'message.text', '[وسائط ماسنجر]'),
            customerName: 'عميل ماسنجر',
        );
    }

    /** @param array<string, mixed> $payload */
    private function processEvolution(array $payload): void
    {
        $merchantId = $payload['merchant_id'] ?? null;
        $senderId = $payload['sender_id'] ?? null;
        $externalMessageId = $payload['message_id'] ?? null;

        if (! is_int($merchantId) && ! ctype_digit((string) $merchantId)) {
            Log::warning('Evolution webhook ignored because merchant ID is invalid.');

            return;
        }

        if (! is_string($senderId) || $senderId === '' || ! is_string($externalMessageId) || $externalMessageId === '') {
            Log::warning('Evolution webhook ignored because message identity is incomplete.');

            return;
        }

        $merchant = User::query()->find((int) $merchantId);
        if (! $merchant) {
            Log::warning('Evolution webhook ignored because the merchant does not exist.', ['merchant_id' => $merchantId]);

            return;
        }

        $this->saveInboundMessage(
            merchant: $merchant,
            platform: 'whatsapp',
            senderId: $senderId,
            messageId: "evolution:{$externalMessageId}",
            body: (string) ($payload['text'] ?? ''),
            customerName: 'عميل واتساب',
        );
    }

    private function saveInboundMessage(
        User $merchant,
        string $platform,
        string $senderId,
        string $messageId,
        string $body,
        string $customerName,
        ?string $mediaId = null,
    ): void {
        $customer = Customer::forMerchant($merchant->id)->firstOrCreate(
            [
                'platform' => $platform,
                'platform_sender_id' => $senderId,
            ],
            [
                'merchant_id' => $merchant->id,
                'name' => $customerName,
            ],
        );

        $message = Message::forMerchant($merchant->id)->updateOrCreate(
            [
                'platform' => $platform,
                'platform_message_id' => $messageId,
            ],
            [
                'merchant_id' => $merchant->id,
                'customer_id' => $customer->id,
                'direction' => 'inbound',
                'message_type' => $mediaId ? 'media' : 'text',
                'body' => $body,
            ],
        );

        $shouldBroadcast = $message->wasRecentlyCreated;

        if ($mediaId !== null && $message->getMedia('chat_media')->isEmpty()) {
            $this->downloadWhatsAppMedia($merchant, $message, $mediaId);
            $shouldBroadcast = true;
        }

        if ($shouldBroadcast) {
            $message->load(['customer', 'media']);
            broadcast(new MessageReceived($message));
        }
    }

    private function downloadWhatsAppMedia(User $merchant, Message $message, string $mediaId): void
    {
        $token = $merchant->whatsapp_access_token ?: config('merchant_integrations.meta.whatsapp_access_token');

        if (! is_string($token) || $token === '') {
            Log::warning('WhatsApp media was not downloaded because the merchant has no access token.', [
                'merchant_id' => $merchant->id,
                'message_id' => $message->id,
            ]);

            return;
        }

        $metadataResponse = Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 250)
            ->get("https://graph.facebook.com/v20.0/{$mediaId}");

        if (! $metadataResponse->successful()) {
            throw new \RuntimeException("Unable to fetch WhatsApp media metadata for {$mediaId}.");
        }

        $url = $metadataResponse->json('url');
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
        $allowedHosts = ['graph.facebook.com', 'lookaside.fbsbx.com'];

        if (! is_string($url) || ! str_starts_with($url, 'https://') || ! in_array($host, $allowedHosts, true)) {
            throw new \RuntimeException("WhatsApp media URL for {$mediaId} is invalid.");
        }

        $fileResponse = Http::withToken($token)
            ->timeout(30)
            ->retry(2, 500)
            ->get($url);

        if (! $fileResponse->successful()) {
            throw new \RuntimeException("Unable to download WhatsApp media {$mediaId}.");
        }

        $content = $fileResponse->body();
        if (strlen($content) > 10 * 1024 * 1024) {
            throw new \RuntimeException("WhatsApp media {$mediaId} exceeds the 10 MB safety limit.");
        }

        $mimeType = strtolower((string) $fileResponse->header('Content-Type'));
        $allowedPrefixes = ['image/', 'audio/', 'video/', 'application/pdf'];
        $isAllowed = collect($allowedPrefixes)->contains(
            fn (string $prefix): bool => str_starts_with($mimeType, $prefix)
        );

        if (! $isAllowed) {
            throw new \RuntimeException("WhatsApp media {$mediaId} has a disallowed MIME type.");
        }

        $extension = match (true) {
            str_starts_with($mimeType, 'image/png') => 'png',
            str_starts_with($mimeType, 'image/') => 'jpg',
            str_starts_with($mimeType, 'audio/') => 'ogg',
            str_starts_with($mimeType, 'video/') => 'mp4',
            $mimeType === 'application/pdf' => 'pdf',
            default => 'bin',
        };

        $message->addMediaFromString($content)
            ->usingFileName("{$mediaId}.{$extension}")
            ->toMediaCollection('chat_media');
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('Inbound webhook job permanently failed.', [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }
}
