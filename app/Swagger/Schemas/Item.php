<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="Item",
 *     type="object",
 *     required={"name", "businessId", "categoryId", "subCategoryId"},
 *     @OA\Property(property="name", type="string", example="Masala Dosa"),
 *     @OA\Property(property="businessId", type="integer", example=1),
 *     @OA\Property(property="categoryId", type="integer", example=5),
 *     @OA\Property(property="subCategoryId", type="integer", example=10),
 *     @OA\Property(property="image", type="string", nullable=true, example="image.jpg"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Delicious dosa"),
 *     @OA\Property(property="status", type="integer", nullable=true, example=1),
 *     @OA\Property(property="price", type="string", nullable=true, example="120"),
 *     @OA\Property(property="isAvailable", type="integer", example=1),
 *     @OA\Property(property="foodType", type="string", nullable=true, example="Veg"),
 *     @OA\Property(property="createdBy", type="integer", nullable=true, example=2),
 *     @OA\Property(property="menuOrderId", type="integer", nullable=true, example=3),
 * )
 */
class Item
{
    // Swagger schema only.
}
