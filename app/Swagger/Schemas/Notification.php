<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="Notification",
 *     type="object",
 *     required={"message", "status", "businessId"},
 *
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="status", type="integer"),
 *     @OA\Property(property="businessId", type="integer"),
 * )
 */
class Notification {}
