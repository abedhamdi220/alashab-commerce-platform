<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    protected MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    // عرض الرسائل من المنصتين في لوحة تحكم واحدة[cite: 1]
    public function index(): JsonResponse
    {
        $messages = $this->messageService->getUnifiedInbox();
        return \App\Support\ApiResponse::make($messages);
    }
    /**
     * إرسال رد من التاجر إلى العميل.
     */
    public function reply(\Illuminate\Http\Request $request, \App\Models\Customer $customer): JsonResponse
    {
        abort_if($customer->merchant_id !== $request->user()->id, 403, 'غير مصرح لك بمراسلة هذا العميل.');

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        // تمرير العميل ونص الرسالة إلى الخدمة لمعالجتها
        $message = $this->messageService->sendReply($customer, $validated['body']);

        if (!$message) {
            return \App\Support\ApiResponse::make(['message' => 'فشل إرسال الرسالة، تأكد من إعدادات الربط.'], 502);
        }

        return \App\Support\ApiResponse::make([
            'message' => 'تم إرسال الرد بنجاح',
            'data' => $message
        ], 201);
    }
}
