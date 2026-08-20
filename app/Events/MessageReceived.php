<?php

namespace App\Events;

use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        $this->message->loadMissing(['customer', 'media']);
    }

    /**
     * تبقى كل رسالة في قناة التاجر المالكة لها فقط.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('merchant.' . $this->message->merchant_id)];
    }

    /**
     * يعيد العقد الموحد نفسه المستعمل في GET /api/messages وPOST reply.
     */
    public function broadcastWith(): array
    {
        return MessageService::toPayload($this->message);
    }
}
