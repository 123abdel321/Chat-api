<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReaction
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatId;
    public $messageId;
    public $userId;
    public $reaction; // 'like', 'love', 'haha', 'wow', 'sad', 'angry'
    public $action; // 'add' or 'remove'

    /**
     * Create a new event instance.
     */
    public function __construct($chatId, $messageId, $userId, $reaction, $action = 'add')
    {
        $this->chatId = $chatId;
        $this->messageId = $messageId;
        $this->userId = $userId;
        $this->reaction = $reaction;
        $this->action = $action;
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
        return 'message.reaction';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'reaction' => $this->reaction,
            'action' => $this->action,
            'timestamp' => now()->toISOString(),
        ];
    }
}
