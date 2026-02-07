<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
    });
});

Route::middleware('auth:sanctum')->post('/test-broadcast', [TestWebSocketController::class, 'testBroadcast']);

Route::middleware('auth:sanctum')->group(function () {
    // Chats
    Route::apiResource('chats', ChatController::class)->except(['update', 'destroy']);
    Route::post('chats/{chat}/participants', [ChatController::class, 'addParticipants']);
    Route::get('search/users', [ChatController::class, 'searchUsers']);
    
    // Messages
    Route::prefix('chats/{chat}')->group(function () {
        Route::apiResource('messages', MessageController::class)->only(['index', 'store']);
        Route::post('messages/{message}/read', [MessageController::class, 'markAsRead']);
        Route::post('typing', [MessageController::class, 'typingStatus']);
        Route::delete('messages/{message}', [MessageController::class, 'destroy']);
    });
    
    // WebSocket authentication
    Route::post('/broadcasting/auth', function () {
        return response()->json(['success' => true]);
    });
});