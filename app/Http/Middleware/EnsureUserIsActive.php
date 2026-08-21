<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->status === 'inactive') {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'status' => STATUS_CODE_NOTAUTHORISED,
                'message' => 'you account access is blocked please contact your headcoach',
                'force_logout' => true,
            ], STATUS_CODE_NOTAUTHORISED);
        }

        return $next($request);
    }
}
