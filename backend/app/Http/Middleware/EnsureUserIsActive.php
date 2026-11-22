<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            $user->token()?->revoke();

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_INACTIVE',
                    'message' => 'Tu cuenta ha sido desactivada por un administrador.',
                    'details' => null,
                ],
            ], 403);
        }

        return $next($request);
    }
}
