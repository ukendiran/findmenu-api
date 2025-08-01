<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="Business",
 *     type="object",
 *     required={"name", "code", "status", "email", "mobile"},
 *     @OA\Property(property="name", type="string", example="Cafe 24/7"),
 *     @OA\Property(property="code", type="string", example="cafe247"),
 *     @OA\Property(property="email", type="string", example="cafe@gmail.com"),
 *     @OA\Property(property="mobile", type="string", example="9876543210"),
 *     @OA\Property(property="address", type="string", example="123 Dosa Street, City, Country"),
 *     @OA\Property(property="logo", type="string", nullable=true, example="image.jpg"),
 *     @OA\Property(property="image", type="string", nullable=true, example="image.jpg"),
 *     @OA\Property(property="bannerImage", type="string", nullable=true, example="image.jpg"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Delicious dosa"),
 *     @OA\Property(property="type", type="string", nullable=true, example="Restaurant"),
 *     @OA\Property(property="status", type="integer", nullable=true, example=1),
 *     @OA\Property(property="currency", type="string", nullable=true, example="rupee"),
 * )
 */
class Business
{
    // Swagger schema only.
}
