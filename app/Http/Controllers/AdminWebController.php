<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWebController extends Controller
{
    public function login()
    {
        return view('admin.login');
    }

    /**
     * تسجيل دخول لوحة الإدارة عبر جلسة web محمية من الخادم. نُصدر توكن
     * Sanctum قصير العمر فقط لتوافق واجهات API الحالية المبنية على Bearer.
     */
    public function authenticate(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('web')->attempt($credentials)) {
            return \App\Support\ApiResponse::make([
                'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ], 422);
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user();
        $token = $user->createToken(
            'merchant-dashboard',
            ['dashboard:access'],
            now()->addHours(8)
        )->plainTextToken;

        return \App\Support\ApiResponse::make([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * ينهي الجلسة ويبطل توكنات لوحة التاجر الصادرة للحساب.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user('web');

        $user?->tokens()->where('name', 'merchant-dashboard')->delete();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return \App\Support\ApiResponse::make([
            'message' => 'تم تسجيل الخروج بنجاح.',
        ]);
    }

    public function dashboard()
    {
        return view('admin.reports-dashboard');
    }

    public function inbox()
    {
        return view('admin.unified-inbox');
    }

    public function customers()
    {
        return view('admin.customers-index');
    }

    public function categories()
    {
        return view('admin.categories-index');
    }

    public function createCategory()
    {
        return redirect()->route('admin.categories.index', ['create' => 1]);
    }

    public function products()
    {
        return view('admin.products-index');
    }

    public function engagement()
    {
        return view('admin.engagement-index');
    }

    public function createProduct()
    {
        return redirect()->route('admin.products.index', ['create' => 1]);
    }

    public function settings()
    {
        return view('admin.settings');
    }

    public function whatsappConnect()
    {
        return view('admin.whatsapp-connect');
    }
}
