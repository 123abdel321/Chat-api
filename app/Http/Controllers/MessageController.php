<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
//EVENTS
use App\Events\MessageSent;
use App\Events\TypingStatus;
//MODELS
use App\Models\Chat;
use App\Models\Message;

class MessageController extends Controller
{
    protected $messages = null;

    public function __construct(Request $request)
	{
		$this->messages = [
            'required' => 'El campo :attribute es requerido.',
            'exists' => 'El :attribute es inválido.',
            'numeric' => 'El campo :attribute debe ser un valor numérico.',
            'string' => 'El campo :attribute debe ser texto',
            'array' => 'El campo :attribute debe ser un arreglo.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'email' => 'El campo :attribute debe ser un email válido.',
            'unique' => 'El :attribute ya está registrado.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'confirmed' => 'La confirmación de :attribute no coincide.',
        ];
	}

    public function index(Chat $chat, Request $request)
    {
        try {

            $this->validatePermissions($chat, $request);

            $messages = $chat->messages()
                ->with(['user', 'attachments'])
                ->orderBy('created_at', 'desc')
                ->paginate(30);

            return response()->json([
                'success' => true,
                'messages' => $messages
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch messages',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Chat $chat, Request $request)
    {
        
        $this->validatePermissions($chat, $request);

        $rules = [
            'body' => 'required_without:attachments|string',
            'type' => 'required|in:text,image,video,audio,file',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|max:10240', // 10MB máximo
        ];

        $validator = Validator::make($request->all(), $rules, $this->messages);

        if ($validator->fails()){
            return response()->json([
                "success" => false,
                "message" => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {

            DB::beginTransaction();

            $message = $chat->messages()->create([
                'user_id' => $request->user()->id,
                'body' => $request->body,
                'type' => $request->type,
            ]);

            // Manejar archivos adjuntos
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments', 'public');
                    
                    $message->attachments()->create([
                        'filename' => basename($path),
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'path' => $path,
                        'size' => $file->getSize(),
                    ]);
                }
            }

            DB::commit();

            // Broadcast el mensaje
            broadcast(new MessageSent($message))->toOthers();

            return response()->json([
                'success' => true,
                'message' => $message->load(['user', 'attachments'])
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Failed to create messages',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markAsRead(Message $message, Request $request)
    {
        if (!$message->chat->isParticipant($request->user()->id)) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $message->markAsRead();
        
        // Broadcast que el mensaje fue leído
        broadcast(new \App\Events\MessageRead($message, $request->user()->id))->toOthers();

        return response()->json(['message' => 'Message marked as read']);
    }

    public function typingStatus(Chat $chat, Request $request)
    {
        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        if (!$chat->isParticipant($request->user()->id)) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        // Broadcast el estado de escritura
        broadcast(new TypingStatus(
            $chat->id,
            $request->user()->id,
            $request->is_typing
        ))->toOthers();

        return response()->json(['message' => 'Typing status updated']);
    }

    public function destroy(Message $message, Request $request)
    {
        // Solo el dueño del mensaje puede eliminarlo
        if ($message->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        // Eliminar archivos adjuntos
        foreach ($message->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->path);
            $attachment->delete();
        }

        $message->delete();

        return response()->json(['message' => 'Message deleted']);
    }

    private function validatePermissions(Chat $chat, Request $request)
    {
        if (!$chat->isParticipant($request->user()->id)) {
            return response()->json([
                'success' => false,
                'messages' => 'Not authorized'
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
