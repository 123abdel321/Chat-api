<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingStatus
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatId;
    public $userId;
    public $isTyping;

    /**
     * Create a new event instance.
     */
    public function __construct($chatId, $userId, $isTyping)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->isTyping = $isTyping;
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
        return 'typing.status';
    }
}
