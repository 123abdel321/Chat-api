<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
//MODELS
use App\Models\User;

class AuthController extends Controller
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

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];

        $validator = Validator::make($request->all(), $rules, $this->messages);

		if ($validator->fails()){
            return response()->json([
                "success"=>false,
                'data' => [],
                "message"=>$validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {

            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'status' => 'offline',
                'last_seen' => now(),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                    'last_seen' => $user->last_seen,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => 'Registration failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules, $this->messages);

		if ($validator->fails()){
            return response()->json([
                "success"=>false,
                'data' => [],
                "message"=>$validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Actualizar estado
            $user->update([
                'status' => 'online',
                'last_seen' => now()
            ]);

            // Crear nuevo token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'status' => $user->status,
                    'last_seen' => $user->last_seen,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Login failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Actualizar estado a offline
            $request->user()->update([
                'status' => 'offline',
                'last_seen' => now()
            ]);
            
            // Revocar token actual
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Logout failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ], Response::HTTP_OK);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $rules = [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
            'avatar' => 'sometimes|image|max:2048',
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

            $data = $request->only(['name', 'phone']);

            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $data['avatar'] = $path;
            }

            $user->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'user' => $user,
                'message' => 'Profile updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => 'Update failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
