<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * نقطة الخروج الموحدة لاستجابات JSON.
     *
     * لا تضيف هذه الطبقة غلافاً مثل success أو data تلقائياً؛ فشكل الـ payload
     * الذي تعتمد عليه الواجهة الحالية يبقى كما هو حرفياً.
     *
     * @param array<string, mixed>|array<int, mixed> $payload
     * @param array<string, string> $headers
     */
    public static function make(array $payload = [], int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json($payload, $status, $headers);
    }
}
