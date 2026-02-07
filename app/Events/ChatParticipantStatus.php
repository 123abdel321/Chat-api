<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
//MODELS
use App\Models\User;

class ChatParticipantStatus
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatId;
    public $user;
    public $isOnline;
    public $action; // 'joined', 'left', 'typing', 'online', 'offline'

    /**
     * Create a new event instance.
     */
    public function __construct($chatId, User $user, $isOnline, $action = 'online')
    {
        $this->chatId = $chatId;
        $this->user = $user->only(['id', 'name', 'avatar']);
        $this->isOnline = $isOnline;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new PresenceChannel('presence.chat.' . $this->chatId);
    }

    public function broadcastAs()
    {
        return 'participant.status';
    }

    public function broadcastWith()
    {
        return [
            'user' => $this->user,
            'is_online' => $this->isOnline,
            'action' => $this->action,
            'timestamp' => now()->toISOString(),
        ];
    }
}
