<?php

namespace App\Http\Controllers;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="FindMenu API",
 *         description="API documentation for FindMenu"
 *     ),
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="bearerAuth",
 *             type="http",
 *             scheme="bearer",
 *             bearerFormat="JWT"
 *         )
 *     )
 * )
 */



use Illuminate\Routing\Controller as BaseLaravelController;

class BaseController extends BaseLaravelController
{
    protected function sendResponse($data, $message = '', $status = 200)
    {
        return response()->json([
            'message' => $message,
            'success' => true,
            'error' => null,
            'data' => $data
        ], $status);
    }

    protected function sendError($message = 'Something went wrong', $error = 'Error', $status = 400)
    {
        return response()->json([
            'message' => $message,
            'success' => false,
            'error' => $error,
            'data' => null
        ], $status);
    }
}
