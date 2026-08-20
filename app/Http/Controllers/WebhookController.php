<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMetaMessageJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function verifyWebhook(Request $request): Response
    {
        $verifyToken = config('merchant_integrations.meta.verify_token');
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if (! is_string($verifyToken) || $verifyToken === '') {
            Log::critical('Meta webhook verification rejected because META_VERIFY_TOKEN is not configured.');

            return \App\Support\ApiResponse::make(['message' => 'Server misconfiguration'], 500);
        }

        if ($mode === 'subscribe' && is_string($token) && hash_equals($verifyToken, $token) && is_string($challenge)) {
            return response($challenge, 200);
        }

        return \App\Support\ApiResponse::make(['message' => 'Forbidden'], 403);
    }

    public function handleWebhook(Request $request): Response
    {
        ProcessMetaMessageJob::dispatch($request->all());

        return response('EVENT_RECEIVED', 200);
    }

    public function handleEvolutionWebhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (($payload['event'] ?? null) !== 'messages.upsert') {
            return \App\Support\ApiResponse::make(['status' => 'IGNORED'], 200);
        }

        $data = $payload['data'] ?? [];
        if (! is_array($data) || (bool) data_get($data, 'key.fromMe', false)) {
            return \App\Support\ApiResponse::make(['status' => 'IGNORED'], 200);
        }

        $instance = $payload['instance'] ?? null;
        if (! is_string($instance) || preg_match('/^merchant_([1-9][0-9]*)$/', $instance, $matches) !== 1) {
            Log::warning('Evolution webhook ignored because instance name is invalid.');

            return \App\Support\ApiResponse::make(['status' => 'IGNORED'], 200);
        }

        $merchantId = (int) $matches[1];
        if (! User::query()->whereKey($merchantId)->exists()) {
            Log::warning('Evolution webhook ignored because merchant does not exist.', ['merchant_id' => $merchantId]);

            return \App\Support\ApiResponse::make(['status' => 'IGNORED'], 200);
        }

        $remoteJid = data_get($data, 'key.remoteJid');
        $messageId = data_get($data, 'key.id');

        if (! is_string($remoteJid) || ! is_string($messageId) || $remoteJid === '' || $messageId === '') {
            return \App\Support\ApiResponse::make(['status' => 'IGNORED'], 200);
        }

        $senderId = strtok($remoteJid, '@');
        if (! is_string($senderId) || $senderId === '') {
            return \App\Support\ApiResponse::make(['status' => 'IGNORED'], 200);
        }

        $text = data_get($data, 'message.conversation')
            ?? data_get($data, 'message.extendedTextMessage.text')
            ?? '';

        ProcessMetaMessageJob::dispatch([
            'platform' => 'whatsapp_evolution',
            'sender_id' => $senderId,
            'instance' => $instance,
            'merchant_id' => $merchantId,
            'message_id' => $messageId,
            'text' => is_string($text) ? $text : '',
        ]);

        return \App\Support\ApiResponse::make(['status' => 'SUCCESS'], 200);
    }
}
