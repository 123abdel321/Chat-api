<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
//MODELS
use App\Models\Chat;
use App\Models\User;


class ChatController extends Controller
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
    
    public function index(Request $request)
    {
        try {
            $user = $request->user();
        
            $chats = $user->chats()
                ->with(['latestMessage', 'participants'])
                ->orderByDesc(function ($query) {
                    $query->select('created_at')
                        ->from('messages')
                        ->whereColumn('chat_id', 'chats.id')
                        ->latest()
                        ->limit(1);
                })
                ->paginate(20);

            return response()->json([
                'success' => true,
                'chats' => $chats
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch chats',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {

        $rules = [
            'user_id' => 'required_if:type,private|exists:users,id',
            'type' => 'required|in:private,group',
            'name' => 'required_if:type,group',
            'participants' => 'required_if:type,group|array',
            'participants.*' => 'exists:users,id',
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

            $user = $request->user();

            if ($request->type === 'private') {
                // Verificar si ya existe un chat privado
                $existingChat = Chat::where('type', 'private')
                    ->whereHas('participants', function ($query) use ($user, $request) {
                        $query->whereIn('user_id', [$user->id, $request->user_id]);
                    })
                    ->get()
                    ->filter(function ($chat) use ($user, $request) {
                        return $chat->participants->count() === 2;
                    })
                    ->first();

                if ($existingChat) {
                    return response()->json([
                        'success' => true,
                        'chat' => $existingChat->load(['participants', 'latestMessage'])
                    ], Response::HTTP_OK);
                }

                $chat = Chat::create([
                    'type' => 'private',
                    'created_by' => $user->id,
                ]);

                // Agregar participantes
                $chat->participants()->attach([$user->id, $request->user_id]);
            } else {
                // Chat grupal
                $chat = Chat::create([
                    'name' => $request->name,
                    'type' => 'group',
                    'created_by' => $user->id,
                    'description' => $request->description,
                ]);

                // Agregar participantes (incluyendo al creador)
                $participants = array_unique(array_merge([$user->id], $request->participants));
                $chat->participants()->attach($participants, ['role' => 'member']);
                
                // El creador es admin
                $chat->participants()->updateExistingPivot($user->id, ['role' => 'admin']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'chat' => $chat->load(['participants'])
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to create chat',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Chat $chat, Request $request)
    {
        // Verificar si el usuario pertenece al chat
        if (!$chat->isParticipant($request->user()->id)) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $chat->load(['participants', 'messages.user']);

        // Marcar mensajes como leídos
        $chat->messages()
            ->where('user_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($chat);
    }

    public function addParticipants(Request $request, Chat $chat)
    {
        $request->validate([
            'participants' => 'required|array',
            'participants.*' => 'exists:users,id',
        ]);

        // Verificar permisos
        if (!$chat->participants()
            ->where('user_id', $request->user()->id)
            ->where('role', 'admin')
            ->exists()) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $chat->participants()->attach($request->participants, ['role' => 'member']);

        return response()->json(['message' => 'Participants added successfully']);
    }

    public function searchUsers(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $users = User::where('id', '!=', $request->user()->id)
            ->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->query}%")
                  ->orWhere('email', 'like', "%{$request->query}%")
                  ->orWhere('phone', 'like', "%{$request->query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone', 'avatar']);

        return response()->json($users);
    }
}
