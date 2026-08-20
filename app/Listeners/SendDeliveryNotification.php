<?php

namespace App\Listeners;

use App\Events\OrderPrepared;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDeliveryNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 5;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900, 1800];

    public function handle(OrderPrepared $event): void
    {
        $order = $event->order->loadMissing(['customer', 'merchant']);
        $customer = $order->customer;
        $merchant = $order->merchant;

        if (! $merchant || ! $customer) {
            Log::error('Delivery notification ignored because order relations are missing.', [
                'order_id' => $order->id,
            ]);

            return;
        }

        if (blank($merchant->delivery_driver_number) || blank($merchant->meta_phone_id)) {
            Log::warning('Delivery notification ignored because merchant WhatsApp settings are incomplete.', [
                'order_id' => $order->id,
                'merchant_id' => $merchant->id,
            ]);

            return;
        }

        $token = $merchant->whatsapp_access_token ?: config('merchant_integrations.meta.whatsapp_access_token');
        if (blank($token)) {
            Log::warning('Delivery notification ignored because merchant WhatsApp access token is missing.', [
                'order_id' => $order->id,
                'merchant_id' => $merchant->id,
            ]);

            return;
        }

        $messageBody = implode("\n", [
            'طلب جديد جاهز للتوصيل',
            "رقم الطلب: {$order->id}",
            "اسم العميل: {$customer->name}",
            "رقم الهاتف: {$customer->phone_number}",
            "المبلغ المطلوب: {$order->total_price}",
        ]);

        try {
            Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->retry(2, 250)
                ->post("https://graph.facebook.com/v20.0/{$merchant->meta_phone_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $merchant->delivery_driver_number,
                    'type' => 'text',
                    'text' => ['body' => $messageBody],
                ])
                ->throw();
        } catch (Throwable $exception) {
            Log::error('Delivery notification request failed.', [
                'order_id' => $order->id,
                'merchant_id' => $merchant->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
