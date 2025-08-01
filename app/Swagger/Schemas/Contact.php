<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="Contact",
 *     type="object",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="mobile", type="string", maxLength=20, nullable=true),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="businessId", type="integer", nullable=true),
 *     @OA\Property(property="message", type="string", nullable=true),
 *     @OA\Property(property="status", type="integer", nullable=true)
 * )
 */
class Contact {}
