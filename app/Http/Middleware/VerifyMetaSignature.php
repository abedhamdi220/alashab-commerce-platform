<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyMetaSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $appSecret = config('merchant_integrations.meta.app_secret');
        $signatureHeader = $request->header('X-Hub-Signature-256');

        if (! is_string($appSecret) || $appSecret === '') {
            Log::critical('Meta webhook rejected because META_APP_SECRET is not configured.');

            return \App\Support\ApiResponse::make(['error' => 'Server misconfiguration'], 500);
        }

        if (! is_string($signatureHeader) || ! str_starts_with($signatureHeader, 'sha256=')) {
            return \App\Support\ApiResponse::make(['error' => 'Unauthorized'], 401);
        }

        $providedSignature = substr($signatureHeader, strlen('sha256='));
        $expectedSignature = hash_hmac('sha256', $request->getContent(), $appSecret);

        if (! hash_equals($expectedSignature, $providedSignature)) {
            Log::warning('Meta webhook rejected because the signature did not match.');

            return \App\Support\ApiResponse::make(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
