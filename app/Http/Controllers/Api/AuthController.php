<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * تسجيل دخول API وإصدار توكن قصير العمر مخصص للوحات التجار.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('web')->attempt($credentials)) {
            return \App\Support\ApiResponse::make([
                'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ], 401);
        }

        $user = Auth::guard('web')->user();
        $token = $user->createToken(
            'merchant-dashboard',
            ['dashboard:access'],
            now()->addHours(8),
        )->plainTextToken;

        return \App\Support\ApiResponse::make([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * إلغاء توكن الطلب الحالي بأمان؛ الطلبات المعتمدة على الجلسة لا تملك توكناً للإلغاء.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return \App\Support\ApiResponse::make(['message' => 'تم تسجيل الخروج بنجاح.']);
    }
}
