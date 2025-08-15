<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $subscription = $user->activeSubscription;

        if (! $subscription) {
            return response()->json([
                'message' => 'Subscription required to access this resource',
            ], 403);
        }

        return $next($request);
    }
}
