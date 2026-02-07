<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
//EVENTS
use App\Events\UserPresenceChanged;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Actualizar last_seen cada 30 segundos máximo
            $shouldUpdate = !$user->last_seen || 
            $user->last_seen->diffInSeconds(now()) > 30;
            
            if ($shouldUpdate) {
                $wasOnline = $user->status === 'online';
                
                $user->update([
                    'last_seen' => now(),
                    'status' => 'online',
                ]);
                
                // Solo emitir evento si cambió de estado
                if (!$wasOnline) {
                    broadcast(new UserPresenceChanged($user, true));
                }
            }
        }

        return $next($request);
    }
}
