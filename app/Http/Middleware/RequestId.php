<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestId
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->headers->get('X-Request-Id') ?? (string) Str::uuid();
        Log::withContext(['request_id' => $id]);
        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }
}
