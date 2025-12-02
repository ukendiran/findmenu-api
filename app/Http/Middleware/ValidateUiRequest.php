<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateUiRequest
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = array_filter([
            config('app.url'),
            config('app.ui_url'),
            config('app.admin_url'),
            config('app.manage_url'),
        ]);

        $referer = $request->header('Referer');
        $origin = $request->header('Origin');
        $userAgent = $request->header('User-Agent');

        $isValidRequest = false;

        // Helper function to check if URL starts with any allowed origin
        $startsWithAllowedOrigin = function ($url) use ($allowedOrigins) {
            foreach ($allowedOrigins as $allowedOrigin) {
                // Compare with trailing slash normalization
                if (str_starts_with(rtrim($url, '/'), rtrim($allowedOrigin, '/'))) {
                    return true;
                }
            }
            return false;
        };

        // Check Referer or Origin header
        if (!empty($referer) && $startsWithAllowedOrigin($referer)) {
            $isValidRequest = true;
        } elseif (!empty($origin) && $startsWithAllowedOrigin($origin)) {
            $isValidRequest = true;
        }

        // Allow AJAX and JSON requests from frontend apps (additional security layer)
        $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest';
        $expectsJson = $request->expectsJson();
        $acceptsJson = $request->header('Accept') && str_contains($request->header('Accept'), 'application/json');

        if (!$isValidRequest && ($isAjax || $expectsJson || $acceptsJson)) {
            $isValidRequest = true;
        }

        // Allow GET requests for testing/development (can be restricted in production)
        if (!$isValidRequest && $request->isMethod('GET')) {
            $isValidRequest = true;
        }

        // Optional: block known malicious user agents (basic example)
        $blockedAgents = ['BadBot', 'SomeOtherBot'];
        foreach ($blockedAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                abort(403, 'Blocked user agent');
            }
        }

        // Final abort if invalid request
        if (!$isValidRequest) {
            abort(403, 'Direct API access not allowed');
        }

        return $next($request);
    }
}
