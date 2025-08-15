<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Item;
use App\Models\MainCategory;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/dashboard/counts",
     *     summary="Get dashboard counts for a business",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="businessId",
     *         in="query",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="Counts retrieved successfully"),
     *     @OA\Response(response=400, description="Missing businessId")
     * )
     */
    public function getCounts(Request $request)
    {
        $businessId = $request->query('businessId');

        if (! $businessId) {
            return $this->sendError('Missing businessId', [], 400);
        }

        $mainCategoryCount = MainCategory::where('status', 1)
            ->where('businessId', $businessId)
            ->count();

        $subCategoryCount = SubCategory::where('status', 1)
            ->where('businessId', $businessId)
            ->count();

        $itemCount = Item::where('status', 1)
            ->where('businessId', $businessId)
            ->count();
        $feedbackCount = Feedback::where('status', operator: 0)
            ->where('businessId', $businessId)
            ->count();

        $data = [
            'main_category_count' => $mainCategoryCount,
            'sub_category_count' => $subCategoryCount,
            'item_count' => $itemCount,
            'feedback_count' => $feedbackCount,
        ];

        return $this->sendResponse($data, 'Dashboard counts retrieved successfully');
    }
}
