<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;


class SubCategoryController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/sub-categories",
     *     tags={"Sub Category"},
     *     summary="Get list of sub categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=false,
     *         description="Filter by sub category name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status (1 = active, 0 = inactive)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="categoryId",
     *         in="query",
     *         required=false,
     *         description="Filter by parent category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="businessId",
     *         in="query",
     *         required=false,
     *         description="Filter by business ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sub categories retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No data found"
     *     )
     * )
     */


    public function index(Request $request)
    {
        $query = SubCategory::query();

        // If user is authenticated, filter by their businessId
        if (auth('api')->check()) {
            $user = auth('api')->user();
            $query->where('businessId', $user->businessId);
        } elseif ($request->has('businessId')) {
            // For public access, allow filtering by businessId parameter
            $query->where('businessId', $request->input('businessId'));
        }

        // Optional filters
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->has('categoryId')) {
            $query->where('categoryId', $request->input('categoryId'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->orderBy('menuOrderId')->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No Sub categories available', 404);
        }

        return $this->sendResponse($data, 'Sub categories retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/sub-categories/with-category",
     *     tags={"Sub Category"},
     *     summary="Get sub categories with related category",
     *     @OA\Parameter(name="name", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="categoryId", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub categories retrieved successfully"),
     *     @OA\Response(response=404, description="No sub categories found")
     * )
     */

    public function withCategory(Request $request)
    {
        $query = SubCategory::query();

        // Optional filters
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->has('categoryId')) {
            $query->where('categoryId', $request->input('categoryId'));
        }

        if ($request->has('businessId')) {
            $query->where('businessId', $request->input('businessId'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        $data = $query->with('category')->orderBy('menuOrderId')->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No Sub categories available', 404);
        }

        return $this->sendResponse($data, 'Sub categories retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/sub-categories",
     *     tags={"Sub Category"},
     *     summary="Create a new sub category",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SubCategory")
     *     ),
     *     @OA\Response(response=200, description="Sub category created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sub_categories')->where(function ($query) use ($request) {
                    return $query->where('businessId', $request->businessId)
                        ->where('categoryId', $request->categoryId);
                }),
            ],
            'description' => 'nullable|string',
            'image'       => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'      => 'nullable|integer',
            'businessId'  => 'sometimes|required|integer|exists:businesses,id',
            'categoryId'  => 'sometimes|required|integer|exists:main_categories,id',
            'isAvailable' => 'nullable|integer',
            'menuOrderId' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $input = $request->except('image', 'code');

        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');
                $folderName = $request->code ?? 'uploads';
                $destinationPath = public_path("images/$folderName");

                // Ensure directory exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                // Generate unique filename with original extension
                $fileName = 'subcat_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

                // Move uploaded file
                $file->move($destinationPath, $fileName);

                $input['image'] = "images/$folderName/$fileName";
            } catch (Exception $e) {
                return $this->sendError('Image Upload Failed', 'Could not process the image upload', 500);
            }
        }
        $data = SubCategory::create($input);
        $data->refresh();

        return $this->sendResponse($data, 'Sub category created successfully');
    }

    /**
     * @OA\Get(
     *     path="/sub-categories/{id}",
     *     tags={"Sub Category"},
     *     summary="Get sub category by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub category retrieved successfully"),
     *     @OA\Response(response=404, description="Sub category not found")
     * )
     */

    public function show($id)
    {
        $data = SubCategory::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'SubCategory not found', 404);
        }
        return $this->sendResponse($data, 'SubCategory retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/sub-categories/{id}",
     *     tags={"Sub Category"},
     *     summary="Update sub category by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SubCategory")
     *     ),
     *     @OA\Response(response=200, description="Sub category updated successfully"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */

    public function update(Request $request, $id)
    {
        // Find the subcategory or return 404
        $subCategory = SubCategory::find($id);
        if (!$subCategory) {
            return $this->sendError('Not Found', 'Sub Category not found', 404);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('sub_categories', 'name')
                    ->where(function ($query) use ($request, $subCategory) {
                        return $query
                            ->where('businessId', $request->businessId ?? $subCategory->businessId)
                            ->where('categoryId', $request->categoryId ?? $subCategory->categoryId);
                    })
                    ->ignore($subCategory->id),
            ],
            'description'   => 'nullable|string',
            'image'         => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'        => 'nullable|integer|in:1,2',
            'businessId'    => 'sometimes|required|integer|exists:businesses,id',
            'categoryId'    => 'sometimes|required|integer|exists:main_categories,id',
            'isAvailable'   => 'nullable|integer|in:1,2',
            'menuOrderId'   => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        // Prepare input data
        $input = $request->except('image', 'code');

        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');
                $folderName = $request->code ?? 'uploads';
                $destinationPath = public_path("images/$folderName");

                // Ensure directory exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                // Generate unique filename with original extension
                $fileName = 'subcat_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

                // Move uploaded file
                $file->move($destinationPath, $fileName);

                // Delete old image if exists
                if ($subCategory->image && file_exists(public_path($subCategory->image))) {
                    @unlink(public_path($subCategory->image));
                }

                $input['image'] = "images/$folderName/$fileName";
            } catch (Exception $e) {
                return $this->sendError('Image Upload Failed', 'Could not process the image upload', 500);
            }
        }

        // Update the record
        if (!$subCategory->update($input)) {
            return $this->sendError('Update failed', 'Could not update Sub category', 500);
        }

        return $this->sendResponse($subCategory->fresh(), 'Sub category updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/sub-categories/{id}",
     *     tags={"Sub Category"},
     *     summary="Delete sub category (soft delete)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub Category deleted successfully"),
     *     @OA\Response(response=404, description="Sub Category not found")
     * )
     */

    public function destroy($id)
    {
        $data = SubCategory::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Sub Category not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Sub Category deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/sub-categories/{id}/restore",
     *     tags={"Sub Category"},
     *     summary="Restore soft deleted sub category",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub category restored successfully"),
     *     @OA\Response(response=404, description="Not found in trash")
     * )
     */

    public function restore($id)
    {
        $data = SubCategory::onlyTrashed()->find($id);

        if (!$data) {
            return $this->sendError('Sub category not found in trash', [], 404);
        }

        $data->restore();

        return $this->sendResponse($data, 'Sub category restored successfully');
    }

    /**
     * @OA\Get(
     *     path="/sub-categories/trashed",
     *     tags={"Sub Category"},
     *     summary="Get all soft-deleted sub categories",
     *     @OA\Response(response=200, description="Trashed categories retrieved successfully"),
     *     @OA\Response(response=404, description="No trashed categories found")
     * )
     */

    public function trashed()
    {
        $data = SubCategory::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed categories found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed categories retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/sub-categories/menu-order",
     *     tags={"Sub Category"},
     *     summary="Update menu order for sub categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 required={"id", "menuOrderId"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="menuOrderId", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Menu order updated successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update menu order"
     *     )
     * )
     */
    public function updateMenuOrder(Request $request)
    {
        // Validate that updateData array exists
        if (!$request->has('updateData')) {
            return $this->sendError('Validation failed', 'The updateData field is required.', 422);
        }

        $updateData = $request->input('updateData');

        // Validate that updateData is an array
        if (!is_array($updateData)) {
            return $this->sendError('Validation failed', 'The updateData field must be an array.', 422);
        }

        // Validate array size to prevent DoS attacks
        if (count($updateData) > 1000) {
            return $this->sendError('Validation failed', 'Cannot update more than 1000 items at once.', 422);
        }

        // Validate that array is not empty
        if (empty($updateData)) {
            return $this->sendError('Validation failed', 'The updateData array cannot be empty.', 422);
        }

        // Validate each item in the array
        $errors = [];
        foreach ($updateData as $index => $item) {
            // Check if item is an array
            if (!is_array($item)) {
                $errors[] = "Item at index {$index} must be an object with id and menuOrderId fields.";
                continue;
            }

            // Check for required fields
            if (!isset($item['id'])) {
                $errors[] = "Item at index {$index} is missing the required 'id' field.";
            }
            if (!isset($item['menuOrderId'])) {
                $errors[] = "Item at index {$index} is missing the required 'menuOrderId' field.";
            }

            // Validate data types
            if (isset($item['id']) && !is_numeric($item['id'])) {
                $errors[] = "Item at index {$index} has invalid 'id' field. Must be a number.";
            }
            if (isset($item['menuOrderId']) && !is_numeric($item['menuOrderId'])) {
                $errors[] = "Item at index {$index} has invalid 'menuOrderId' field. Must be a number.";
            }

            // Validate positive integers
            if (isset($item['id']) && is_numeric($item['id']) && $item['id'] <= 0) {
                $errors[] = "Item at index {$index} has invalid 'id' field. Must be a positive integer.";
            }
            if (isset($item['menuOrderId']) && is_numeric($item['menuOrderId']) && $item['menuOrderId'] < 0) {
                $errors[] = "Item at index {$index} has invalid 'menuOrderId' field. Must be a non-negative integer.";
            }
        }

        // Return validation errors if any
        if (!empty($errors)) {
            return $this->sendError('Validation failed', $errors, 422);
        }

        // Extract all entity IDs
        $entityIds = array_column($updateData, 'id');

        // Verify all entities exist in database
        $existingIds = SubCategory::whereIn('id', $entityIds)->pluck('id')->toArray();
        $missingIds = array_diff($entityIds, $existingIds);

        if (!empty($missingIds)) {
            return $this->sendError(
                'Entity not found',
                'The following sub category IDs do not exist: ' . implode(', ', $missingIds),
                404
            );
        }

        // Get authenticated user's businessId
        $user = auth('api')->user();
        if (!$user) {
            return $this->sendError('Unauthorized', 'Authentication required.', 401);
        }

        $userBusinessId = $user->businessId;

        // Verify all entities belong to the authenticated user's business
        $entities = SubCategory::whereIn('id', $entityIds)->get();
        $unauthorizedIds = [];

        foreach ($entities as $entity) {
            if ($entity->businessId != $userBusinessId) {
                $unauthorizedIds[] = $entity->id;
            }
        }

        if (!empty($unauthorizedIds)) {
            // Log security violation
            Log::warning("Unauthorized menu order update attempt", [
                'user_id' => $user->id,
                'user_business_id' => $userBusinessId,
                'attempted_subcategory_ids' => $unauthorizedIds,
                'ip_address' => request()->ip()
            ]);

            return $this->sendError(
                'Authorization failed',
                'You do not have permission to modify these sub categories. Sub category IDs: ' . implode(', ', $unauthorizedIds),
                403
            );
        }

        try {
            // Log transaction start
            Log::info("SubCategory menu order update started", [
                'user_id' => $user->id,
                'business_id' => $userBusinessId,
                'entity_count' => count($updateData),
                'entity_ids' => $entityIds
            ]);

            DB::beginTransaction();
            
            foreach ($updateData as $value) {
                SubCategory::where('id', $value['id'])->update([
                    'menuOrderId' => $value['menuOrderId'],
                ]);
            }

            DB::commit();

            // Log successful transaction
            Log::info("SubCategory menu order update successful", [
                'user_id' => $user->id,
                'business_id' => $userBusinessId,
                'entity_count' => count($updateData)
            ]);

            return $this->sendResponse([], 'Menu order updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            
            // Log detailed error information
            Log::error("SubCategory menu order update failed", [
                'user_id' => $user->id,
                'business_id' => $userBusinessId,
                'entity_count' => count($updateData),
                'entity_ids' => $entityIds,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Failed to update menu order', 'An error occurred while updating menu order. Please try again.', 500);
        }
    }
}
