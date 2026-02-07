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

class UserPresenceChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $isOnline;
    public $lastSeen;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, $isOnline)
    {
        $this->user = $user->only(['id', 'name', 'email', 'avatar', 'status']);
        $this->isOnline = $isOnline;
        $this->lastSeen = $user->last_seen;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return new PresenceChannel('presence.users');
    }

    public function broadcastAs()
    {
        return 'user.presence.changed';
    }

    public function broadcastWith()
    {
        return [
            'user' => $this->user,
            'is_online' => $this->isOnline,
            'last_seen' => $this->lastSeen,
        ];
    }
}
