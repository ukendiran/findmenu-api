<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="Feedback",
 *     type="object",
 *     required={"message"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="message", type="string", example="Great service!"),
 *     @OA\Property(property="status", type="integer", example=1),
 *     @OA\Property(property="businessId", type="integer", example=5),
 * )
 */
class Feedback {}
