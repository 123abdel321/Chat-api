<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatId;
    public $messageId;
    public $deletedBy;

    /**
     * Create a new event instance.
     */
    public function __construct($chatId, $messageId, $deletedBy)
    {
        $this->chatId = $chatId;
        $this->messageId = $messageId;
        $this->deletedBy = $deletedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chatId);
    }

    public function broadcastAs()
    {
        return 'message.deleted';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'deleted_by' => $this->deletedBy,
            'deleted_at' => now()->toISOString(),
        ];
    }
}
