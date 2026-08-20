<?php

return [
    /* يمكن تعطيل سجل تغييرات النماذج مؤقتاً من البيئة من دون تعديل الكود. */
    'enabled' => env('MODEL_ACTIVITY_LOG_ENABLED', true),

    /* عدد الأيام المحتفَظ بها في ملفات السجل اليومية لكل نموذج. */
    'days' => env('MODEL_ACTIVITY_LOG_DAYS', 30),

    'level' => env('MODEL_ACTIVITY_LOG_LEVEL', 'info'),

    /* لا يُسجّل عنوان IP افتراضياً لتقليل البيانات الشخصية في السجلات. */
    'include_ip' => env('MODEL_ACTIVITY_LOG_INCLUDE_IP', false),

    /*
     * السجل يوثق أسماء الحقول المتأثرة فقط، وليس قيمها. وتُستبعد هذه الأسماء
     * كذلك لمنع كشف أن السرّ تم تعديله في سجل تشغيلي عادي.
     */
    'excluded_attributes' => [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'api_token',
        'access_token',
        'refresh_token',
        'authorization',
        'secret',
        'webhook_secret',
        'meta_access_token',
        'whatsapp_token',
    ],
];
