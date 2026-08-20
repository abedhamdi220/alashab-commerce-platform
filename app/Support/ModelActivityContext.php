<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class ModelActivityContext
{
    /**
     * يبني سياقاً قابلاً للتتبع من دون تضمين بيانات الطلب أو معلومات حساسة.
     *
     * @return array<string, mixed>
     */
    public static function current(): array
    {
        if (!app()->bound('request')) {
            return [
                'source' => 'console',
            ];
        }

        $request = request();
        $requestId = $request->headers->get('X-Request-Id')
            ?? $request->headers->get('X-Correlation-Id')
            ?? $request->attributes->get('model_activity.request_id');

        if ($requestId === null) {
            $requestId = (string) Str::uuid();
            $request->attributes->set('model_activity.request_id', $requestId);
        }

        return array_filter([
            'source' => app()->runningInConsole() ? 'console' : 'http',
            'request_id' => $requestId,
            'method' => $request->method(),
            'route' => optional($request->route())->getName(),
            'path' => $request->path(),
            'ip' => config('model-activity.include_ip', false) ? $request->ip() : null,
            'actor_id' => optional($request->user())->getAuthIdentifier(),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
