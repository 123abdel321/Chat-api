<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
//MODELS
use App\Models\Message;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $chatId;

    public function __construct(Message $message)
    {
        $this->message = $message->load(['user', 'attachments']);
        $this->chatId = $message->chat_id;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chatId);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'body' => $this->message->body,
                'type' => $this->message->type,
                'chat_id' => $this->message->chat_id,
                'user' => [
                    'id' => $this->message->user->id,
                    'name' => $this->message->user->name,
                    'avatar' => $this->message->user->avatar,
                ],
                'created_at' => $this->message->created_at->toISOString(),
                'updated_at' => $this->message->updated_at->toISOString(),
                'attachments' => $this->message->attachments->map(function($attachment) {
                    return [
                        'id' => $attachment->id,
                        'filename' => $attachment->filename,
                        'original_name' => $attachment->original_name,
                        'url' => $attachment->url,
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                    ];
                }),
                'metadata' => $this->message->metadata,
            ],
            'chat_id' => $this->chatId,
            'sent_at' => now()->toISOString(),
        ];
    }
}