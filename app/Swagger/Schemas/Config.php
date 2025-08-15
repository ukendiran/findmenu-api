<?php

namespace App\Swagger\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Config",
 *     type="object",
 *     required={"status", "businessId"},
 *
 *     @OA\Property(property="json", type="string", format="json", nullable=true),
 *     @OA\Property(property="status", type="integer", example=1),
 *     @OA\Property(property="businessId", type="integer", example=1),
 *     @OA\Property(property="googleReviewStatus", type="integer", nullable=true, example=1),
 *     @OA\Property(property="googleReview", type="string", nullable=true, example="https://google.com/review"),
 *     @OA\Property(property="wifiPassword", type="string", maxLength=20, nullable=true, example="password123"),
 *     @OA\Property(property="wifiPasswordStatus", type="integer", nullable=true, example=1),
 *     @OA\Property(property="instagramStatus", type="integer", nullable=true, example=1),
 *     @OA\Property(property="instagram", type="string", maxLength=255, nullable=true, example="https://instagram.com/business"),
 *     @OA\Property(property="review", type="integer", nullable=true, example=100),
 *     @OA\Property(property="reviewStatus", type="integer", nullable=true, example=1),
 *     @OA\Property(property="stars", type="string", maxLength=10, nullable=true, example="4.5"),
 *     @OA\Property(property="starsStatus", type="integer", nullable=true, example=1),
 *     @OA\Property(property="googleMapStatus", type="integer", nullable=true, example=1),
 *     @OA\Property(property="googleMap", type="string", nullable=true, example="https://maps.google.com/location"),
 *     @OA\Property(property="showFeedbackFormStatus", type="integer", nullable=true, example=1),
 *     @OA\Property(property="facebookStatus", type="integer", nullable=true, example=1),
 *     @OA\Property(property="facebook", type="string", maxLength=255, nullable=true, example="https://facebook.com/business"),
 *     @OA\Property(property="youtubeStatus", type="integer", nullable=true, example=1),
 *     @OA\Property(property="youtube", type="string", maxLength=255, nullable=true, example="https://youtube.com/business"),
 *     @OA\Property(property="whatsappStatus", type="integer", nullable=true, example=1),
 *     @OA\Property(property="whatsapp", type="string", maxLength=255, nullable=true, example="https://wa.me/123456789"),
 *     @OA\Property(property="tripadvisor", type="string", maxLength=255, nullable=true, example="https://tripadvisor.com/business"),
 *     @OA\Property(property="tripadvisorStatus", type="integer", nullable=true, example=1),
 * )
 */
class Config
{
    // Swagger schema only.
}
