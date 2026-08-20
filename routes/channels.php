<?php

use Illuminate\Support\Facades\Broadcast;

// التحقق من أن المستخدم (التاجر) المسجل دخوله يطلب الاستماع لرسائل متجره الخاص فقط
Broadcast::channel('merchant.{merchantId}', function ($user, $merchantId) {
    return (int) $user->id === (int) $merchantId;
});
