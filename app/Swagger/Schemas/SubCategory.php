<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="SubCategory",
 *     type="object",
 *     required={"name", "businessId"},
 *
 *     @OA\Property(property="name", type="string", example="Masala Dosa"),
 *     @OA\Property(property="businessId", type="integer", example=1),
 *     @OA\Property(property="categoryId", type="integer", example=2),
 *     @OA\Property(property="image", type="string", nullable=true, example="image.jpg"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Delicious dosa"),
 *     @OA\Property(property="status", type="integer", nullable=true, example=1),
 *     @OA\Property(property="isAvailable", type="integer", example=1),
 *     @OA\Property(property="menuOrderId", type="integer", nullable=true, example=3),
 * )
 */
class SubCategory
{
    // Swagger schema only.
}
