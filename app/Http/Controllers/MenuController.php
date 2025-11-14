<?php

namespace App\Http\Controllers;

use App\Models\MainCategory;
use App\Models\SubCategory;
use App\Models\Item;
use Illuminate\Http\Request;

class MenuController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/menu/complete",
     *     tags={"Menu"},
     *     summary="Get complete menu structure with categories, subcategories, and items",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="businessId",
     *         in="query",
     *         required=false,
     *         description="Filter by business ID (optional if authenticated)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Complete menu retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No menu data found"
     *     )
     * )
     */
    public function getCompleteMenu(Request $request)
    {
        // Determine businessId
        $businessId = null;
        
        try {
            if (auth('api')->check()) {
                $user = auth('api')->user();
                $businessId = $user->businessId;
            }
        } catch (\Exception $e) {
            // If authentication fails, continue to check for businessId parameter
        }
        
        if (!$businessId && $request->has('businessId')) {
            $businessId = $request->input('businessId');
        }

        if (!$businessId) {
            return $this->sendError('Business ID required', 'Please provide a business ID or authenticate', 400);
        }

        // Fetch all categories for the business, ordered by menuOrderId
        $categories = MainCategory::where('businessId', $businessId)
            ->orderBy('menuOrderId')
            ->get();

        if ($categories->isEmpty()) {
            return $this->sendError('No data found', 'No menu data available for this business', 404);
        }

        // Get all category IDs
        $categoryIds = $categories->pluck('id');

        // Fetch all subcategories for these categories, ordered by menuOrderId
        $subcategories = SubCategory::whereIn('categoryId', $categoryIds)
            ->orderBy('menuOrderId')
            ->get()
            ->groupBy('categoryId');

        // Get all subcategory IDs
        $subcategoryIds = $subcategories->flatten()->pluck('id');

        // Fetch all items for this business, ordered by menuOrderId
        $items = Item::where('businessId', $businessId)
            ->orderBy('menuOrderId')
            ->get();

        // Group items by their parent (category or subcategory)
        $itemsBySubcategory = $items->where('subCategoryId', '!=', null)
            ->groupBy('subCategoryId');
        $itemsByCategory = $items->where('subCategoryId', null)
            ->groupBy('categoryId');

        // Build the hierarchical structure
        $menuStructure = $categories->map(function ($category) use ($subcategories, $itemsBySubcategory, $itemsByCategory) {
            $categoryId = $category->id;

            // Get subcategories for this category
            $categorySubcategories = $subcategories->get($categoryId, collect());

            // Build subcategories with their items
            $subcategoriesWithItems = $categorySubcategories->map(function ($subcategory) use ($itemsBySubcategory) {
                $subcategoryItems = $itemsBySubcategory->get($subcategory->id, collect());

                return [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'description' => $subcategory->description,
                    'image' => $subcategory->image,
                    'status' => $subcategory->status,
                    'isAvailable' => $subcategory->isAvailable,
                    'menuOrderId' => $subcategory->menuOrderId,
                    'categoryId' => $subcategory->categoryId,
                    'businessId' => $subcategory->businessId,
                    'items' => $subcategoryItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'description' => $item->description,
                            'image' => $item->image,
                            'price' => $item->price,
                            'status' => $item->status,
                            'isAvailable' => $item->isAvailable,
                            'menuOrderId' => $item->menuOrderId,
                            'categoryId' => $item->categoryId,
                            'subCategoryId' => $item->subCategoryId,
                            'businessId' => $item->businessId,
                        ];
                    })->values(),
                    'itemCount' => $subcategoryItems->count(),
                ];
            })->values();

            // Get direct items (items without subcategory)
            $directItems = $itemsByCategory->get($categoryId, collect());

            $directItemsFormatted = $directItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'image' => $item->image,
                    'price' => $item->price,
                    'status' => $item->status,
                    'isAvailable' => $item->isAvailable,
                    'menuOrderId' => $item->menuOrderId,
                    'categoryId' => $item->categoryId,
                    'subCategoryId' => $item->subCategoryId,
                    'businessId' => $item->businessId,
                ];
            })->values();

            // Calculate total item count
            $totalItemCount = $directItems->count() + $subcategoriesWithItems->sum('itemCount');

            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'image' => $category->image,
                'status' => $category->status,
                'isAvailable' => $category->isAvailable,
                'menuOrderId' => $category->menuOrderId,
                'businessId' => $category->businessId,
                'subcategories' => $subcategoriesWithItems,
                'items' => $directItemsFormatted,
                'itemCount' => $totalItemCount,
            ];
        });

        return $this->sendResponse($menuStructure, 'Complete menu retrieved successfully');
    }

    /**
     * Get complete menu structure by business code
     */
    public function getCompleteMenuByCode($code)
    {
        // Find business by code
        $business = \App\Models\Business::where('code', $code)->first();

        if (!$business) {
            return $this->sendError('Business not found', 'No business found with code: ' . $code, 404);
        }

        $businessId = $business->id;

        // Fetch all categories for the business, ordered by menuOrderId
        $categories = MainCategory::where('businessId', $businessId)
            ->orderBy('menuOrderId')
            ->get();

        if ($categories->isEmpty()) {
            return $this->sendError('No data found', 'No menu data available for this business', 404);
        }

        // Get all category IDs
        $categoryIds = $categories->pluck('id');

        // Fetch all subcategories for these categories, ordered by menuOrderId
        $subcategories = SubCategory::whereIn('categoryId', $categoryIds)
            ->orderBy('menuOrderId')
            ->get()
            ->groupBy('categoryId');

        // Get all subcategory IDs
        $subcategoryIds = $subcategories->flatten()->pluck('id');

        // Fetch all items for this business, ordered by menuOrderId
        $items = Item::where('businessId', $businessId)
            ->orderBy('menuOrderId')
            ->get();

        // Group items by their parent (category or subcategory)
        $itemsBySubcategory = $items->where('subCategoryId', '!=', null)
            ->groupBy('subCategoryId');
        $itemsByCategory = $items->where('subCategoryId', null)
            ->groupBy('categoryId');

        // Build the hierarchical structure
        $menuStructure = $categories->map(function ($category) use ($subcategories, $itemsBySubcategory, $itemsByCategory) {
            $categoryId = $category->id;

            // Get subcategories for this category
            $categorySubcategories = $subcategories->get($categoryId, collect());

            // Build subcategories with their items
            $subcategoriesWithItems = $categorySubcategories->map(function ($subcategory) use ($itemsBySubcategory) {
                $subcategoryItems = $itemsBySubcategory->get($subcategory->id, collect());

                return [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'description' => $subcategory->description,
                    'image' => $subcategory->image,
                    'status' => $subcategory->status,
                    'isAvailable' => $subcategory->isAvailable,
                    'menuOrderId' => $subcategory->menuOrderId,
                    'categoryId' => $subcategory->categoryId,
                    'businessId' => $subcategory->businessId,
                    'items' => $subcategoryItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'description' => $item->description,
                            'image' => $item->image,
                            'price' => $item->price,
                            'status' => $item->status,
                            'isAvailable' => $item->isAvailable,
                            'menuOrderId' => $item->menuOrderId,
                            'categoryId' => $item->categoryId,
                            'subCategoryId' => $item->subCategoryId,
                            'businessId' => $item->businessId,
                        ];
                    })->values(),
                    'itemCount' => $subcategoryItems->count(),
                ];
            })->values();

            // Get direct items (items without subcategory)
            $directItems = $itemsByCategory->get($categoryId, collect());

            $directItemsFormatted = $directItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'image' => $item->image,
                    'price' => $item->price,
                    'status' => $item->status,
                    'isAvailable' => $item->isAvailable,
                    'menuOrderId' => $item->menuOrderId,
                    'categoryId' => $item->categoryId,
                    'subCategoryId' => $item->subCategoryId,
                    'businessId' => $item->businessId,
                ];
            })->values();

            // Calculate total item count
            $totalItemCount = $directItems->count() + $subcategoriesWithItems->sum('itemCount');

            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'image' => $category->image,
                'status' => $category->status,
                'isAvailable' => $category->isAvailable,
                'menuOrderId' => $category->menuOrderId,
                'businessId' => $category->businessId,
                'subcategories' => $subcategoriesWithItems,
                'items' => $directItemsFormatted,
                'itemCount' => $totalItemCount,
            ];
        });

        return $this->sendResponse($menuStructure, 'Complete menu retrieved successfully');
    }
}
