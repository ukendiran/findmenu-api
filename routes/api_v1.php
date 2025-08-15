<?php

use App\Http\Controllers\Api\V1\HealthcheckController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthcheckController::class);
    // TODO: Auth + menus endpoints here, under ->middleware('auth:sanctum') as needed.
});
