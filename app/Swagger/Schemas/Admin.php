<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="Admin",
 *     type="object",
 *     required={"name","email", "password", "mobile"},
 *    @OA\Property(property="name", type="string"),
 *    @OA\Property(property="email", type="string"),
 *    @OA\Property(property="password", type="string"),
 *    @OA\Property(property="mobile", type="string"),
 *    @OA\Property(property="image", type="string"),
 *    @OA\Property(property="status", type="integer"),
 * )
 */
class Admin
{
    // Swagger schema only.
}
