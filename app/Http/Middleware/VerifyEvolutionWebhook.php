<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyEvolutionWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('merchant_integrations.evolution.webhook_secret');
        $header = (string) config('merchant_integrations.evolution.webhook_header', 'X-Evolution-Webhook-Secret');
        $provided = $request->header($header);

        if (! is_string($expected) || $expected === '' || ! is_string($provided) || ! hash_equals($expected, $provided)) {
            Log::warning('Evolution webhook rejected because its authentication header is invalid.');

            return \App\Support\ApiResponse::make(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
