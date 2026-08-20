<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppConnectionController extends Controller
{
    private function instanceName(): string
    {
        return 'merchant_' . Auth::id();
    }

    /** @return array{base_url: string, api_key: mixed, webhook_url: string, webhook_secret: mixed, webhook_header: string} */
    private function settings(): array
    {
        return [
            'base_url' => rtrim((string) config('merchant_integrations.evolution.base_url'), '/'),
            'api_key' => config('merchant_integrations.evolution.api_key'),
            'webhook_url' => (string) config('merchant_integrations.evolution.webhook_url'),
            'webhook_secret' => config('merchant_integrations.evolution.webhook_secret'),
            'webhook_header' => (string) config('merchant_integrations.evolution.webhook_header', 'X-Evolution-Webhook-Secret'),
        ];
    }

    private function isConfigured(array $settings): bool
    {
        return $settings['base_url'] !== ''
            && ! blank($settings['api_key'])
            && filter_var($settings['webhook_url'], FILTER_VALIDATE_URL) !== false
            && ! blank($settings['webhook_secret']);
    }

    private function client(array $settings): PendingRequest
    {
        return Http::withHeaders([
            'apikey' => $settings['api_key'],
            'Accept' => 'application/json',
        ])->connectTimeout(5)->timeout(20);
    }

    /** @return array<string, mixed> */
    private function webhookConfiguration(array $settings): array
    {
        return [
            'enabled' => true,
            'url' => $settings['webhook_url'],
            'headers' => [
                $settings['webhook_header'] => $settings['webhook_secret'],
            ],
            'byEvents' => false,
            'base64' => false,
            'events' => [
                'MESSAGES_UPSERT',
                'CONNECTION_UPDATE',
                'QRCODE_UPDATED',
            ],
        ];
    }

    /** @return string|null null means that the instance does not exist yet. */
    private function connectionState(string $instance, array $settings): ?string
    {
        $response = $this->client($settings)->get("{$settings['base_url']}/instance/connectionState/{$instance}");

        // بعض إصدارات Evolution تعيد 400 والبعض الآخر 404 عندما لا توجد instance بعد.
        if (in_array($response->status(), [400, 404], true)) {
            return null;
        }

        if (! $response->successful()) {
            throw new \RuntimeException("Evolution state request failed with status {$response->status()}.");
        }

        $state = $response->json('instance.state')
            ?? $response->json('instance.connectionStatus')
            ?? $response->json('state');

        return is_string($state) ? strtolower($state) : 'close';
    }

    private function createInstance(string $instance, array $settings): void
    {
        $response = $this->client($settings)->post("{$settings['base_url']}/instance/create", [
            'instanceName' => $instance,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
            'alwaysOnline' => true,
            'readMessages' => false,
            'readStatus' => false,
            'groupsIgnore' => true,
            // حقول إنشاء instance الرسمية؛ الترويسة الخاصة تضبط في خطوة webhook التالية.
            'webhookUrl' => $settings['webhook_url'],
            'webhookByEvents' => false,
            'webhookBase64' => false,
            'webhookEvents' => [
                'MESSAGES_UPSERT',
                'CONNECTION_UPDATE',
                'QRCODE_UPDATED',
            ],
        ]);

        if (! $response->successful() && ! in_array($response->status(), [409], true)) {
            throw new \RuntimeException("Evolution instance creation failed with status {$response->status()}.");
        }
    }

    private function configureWebhook(string $instance, array $settings): void
    {
        $response = $this->client($settings)->post("{$settings['base_url']}/webhook/set/{$instance}", [
            'webhook' => $this->webhookConfiguration($settings),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Evolution webhook configuration failed with status {$response->status()}.");
        }
    }

    /** @return array{state: string, qrcode: ?string} */
    private function requestConnection(string $instance, array $settings): array
    {
        $response = $this->client($settings)->get("{$settings['base_url']}/instance/connect/{$instance}");

        if (! $response->successful()) {
            throw new \RuntimeException("Evolution connection request failed with status {$response->status()}.");
        }

        $data = $response->json();
        $state = data_get($data, 'instance.state')
            ?? data_get($data, 'instance.connectionStatus')
            ?? data_get($data, 'state')
            ?? 'connecting';

        $qrcode = data_get($data, 'qrcode.base64')
            ?? data_get($data, 'base64')
            ?? data_get($data, 'qrcode');

        return [
            'state' => is_string($state) ? strtolower($state) : 'connecting',
            'qrcode' => is_string($qrcode) ? $qrcode : null,
        ];
    }

    public function connect(): JsonResponse
    {
        $settings = $this->settings();

        if (! $this->isConfigured($settings)) {
            Log::critical('Evolution connect rejected because integration settings are missing.');

            return \App\Support\ApiResponse::make([
                'success' => false,
                'message' => 'إعدادات محرك واتساب غير مكتملة. راجع EVOLUTION_BASE_URL وEVOLUTION_API_KEY وEVOLUTION_WEBHOOK_SECRET.',
            ], 503);
        }

        $instance = $this->instanceName();

        try {
            $state = $this->connectionState($instance, $settings);

            if ($state === 'open') {
                // تصلح الجلسات التي أُنشئت قبل إضافة webhook الخاص بالتاجر.
                $this->configureWebhook($instance, $settings);

                return \App\Support\ApiResponse::make([
                    'success' => true,
                    'state' => 'open',
                    'qrcode' => null,
                    'instance' => $instance,
                ]);
            }

            if ($state === null) {
                $this->createInstance($instance, $settings);
            }

            // يعيد ضبط الأحداث والترويسة للحالات القديمة والجديدة قبل بدء الاتصال.
            $this->configureWebhook($instance, $settings);
            $connection = $this->requestConnection($instance, $settings);

            return \App\Support\ApiResponse::make([
                'success' => true,
                'state' => $connection['state'],
                'qrcode' => $connection['qrcode'],
                'instance' => $instance,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Evolution connect request failed.', [
                'merchant_id' => Auth::id(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return \App\Support\ApiResponse::make([
                'success' => false,
                'message' => 'تعذر إنشاء أو استعادة جلسة واتساب للتاجر. تحقق من توفر محرك Evolution وإعدادات الربط.',
            ], 502);
        }
    }

    public function status(): JsonResponse
    {
        $settings = $this->settings();

        if (! $this->isConfigured($settings)) {
            return \App\Support\ApiResponse::make(['success' => false, 'state' => 'unavailable'], 503);
        }

        try {
            $state = $this->connectionState($this->instanceName(), $settings);

            return \App\Support\ApiResponse::make([
                'success' => true,
                'state' => $state ?? 'close',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Evolution status request failed.', [
                'merchant_id' => Auth::id(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return \App\Support\ApiResponse::make(['success' => false, 'state' => 'close']);
        }
    }
}
