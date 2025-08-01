<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"name", "businessId","email", "password", "mobile"},
 *    @OA\Property(property="name", type="string"),
 *    @OA\Property(property="email", type="string"),
 *    @OA\Property(property="password", type="string"),
 *    @OA\Property(property="mobile", type="string"),
 *    @OA\Property(property="businessId", type="integer"),
 *    @OA\Property(property="phone", type="string"),
 *    @OA\Property(property="gender", type="string", enum={"Male", "Female", "Other"}),
 *    @OA\Property(property="profileImage", type="string"),
 *    @OA\Property(property="image", type="string"),
 *    @OA\Property(property="status", type="integer"),
 * )
 */
class User
{
    // Swagger schema only.
}
