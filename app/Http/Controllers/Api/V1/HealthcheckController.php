<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;

class HealthcheckController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'ok' => true,
            'service' => 'findmenu-api',
            'version' => config('app.version', '0.1.0'),
            'time' => now()->toIso8601String(),
        ]);
    }
}
