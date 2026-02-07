<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;

class TestWebSocketController extends Controller
{
    public function testBroadcast(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|exists:chats,id',
            'message' => 'required|string',
        ]);

        // Crear mensaje de prueba
        $message = Message::create([
            'chat_id' => $request->chat_id,
            'user_id' => $request->user()->id,
            'body' => $request->message,
            'type' => 'text',
        ]);

        // Disparar evento
        broadcast(new MessageSent($message));

        return response()->json([
            'success' => true,
            'message' => 'Event broadcasted',
        ]);
    }
}