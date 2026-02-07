<?php

use Illuminate\Support\Facades\Broadcast;
//MODELS
use App\Models\Chat;

Broadcast::channel('presence.users', function (User $user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $user->avatar,
        'status' => $user->status,
    ];
});

Broadcast::channel('presence.chat.{chatId}', function (User $user, $chatId) {
    $chat = Chat::find($chatId);
    
    if (!$chat || !$chat->isParticipant($user->id)) {
        return false;
    }

    $role = $chat->participants()
        ->where('user_id', $user->id)
        ->first()
        ->pivot
        ->role ?? 'member';
    
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar,
        'role' => $role
    ];
});

// Canal privado para chats (ya existente, verificar)
Broadcast::channel('chat.{chatId}', function (User $user, $chatId) {
    $chat = Chat::find($chatId);
    return $chat && $chat->isParticipant($user->id);
});
