<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Item;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;


class ItemController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/items",
     *     tags={"Item"},
     *     summary="Get list of items",
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
     *         name="subCategoryId",
     *         in="query",
     *         required=false,
     *         description="Filter by parent sub category ID",
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
     *         description="Items retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No data found"
     *     )
     * )
     */


    public function index(Request $request)
    {
        $query = Item::query();

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

        $data = $query->with(['category', 'subCategory'])->orderBy('menuOrderId')->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No Items available', 404);
        }

        return $this->sendResponse($data, 'Items retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/items/with-category",
     *     tags={"Item"},
     *     summary="Get items with related category",
     *     @OA\Parameter(name="name", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="categoryId", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subCategoryId", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Items retrieved successfully"),
     *     @OA\Response(response=404, description="No items found")
     * )
     */

    public function withCategory(Request $request)
    {
        $query = Item::query();

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
        $data = $query->with(['category', 'subCategory'])->orderBy('menuOrderId')->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No Items available', 404);
        }

        return $this->sendResponse($data, 'Items retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/items",
     *     tags={"Item"},
     *     summary="Create a new sub category",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Item")
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
                Rule::unique('items')->where(function ($query) use ($request) {
                    return $query->where('businessId', $request->businessId)
                        ->where('categoryId', $request->categoryId)
                        ->where('status', 1);
                }),
            ],
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp',
            'status'      => 'nullable|integer',
            'businessId'  => 'sometimes|required|integer|exists:businesses,id',
            'categoryId'  => 'sometimes|required|integer|exists:main_categories,id',
            'isAvailable' => 'nullable|integer',
            'menuOrderId' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        $input = $request->except('image'); // Get all except image
        // Handle new images
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $folderName = $request->code ?? 'uploads';
            $destinationPath = public_path("images/$folderName");
            $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            $file->move($destinationPath, $fileName);

            $input['image'] = "images/$folderName/$fileName";
        }

        $item = Item::create($input);

        return response()->json([
            'success' => true,
            'data' => $item,
            'message' => 'Item created successfully'
        ]);
    }


    /**
     * @OA\Get(
     *     path="/items/{id}",
     *     tags={"Item"},
     *     summary="Get sub category by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub category retrieved successfully"),
     *     @OA\Response(response=404, description="Sub category not found")
     * )
     */

    public function show($id)
    {
        $data = Item::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'Item not found', 404);
        }
        return $this->sendResponse($data, 'Item retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/items/{id}",
     *     tags={"Item"},
     *     summary="Update sub category by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Item")
     *     ),
     *     @OA\Response(response=200, description="Sub category updated successfully"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */

    public function update(Request $request, $id)
    {
        $data = Item::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'Item not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('items', 'name')
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('businessId', $request->businessId)
                            ->where('categoryId', $request->categoryId)
                            ->where('status', 1);
                    })
                    ->ignore($data->id, 'id'),
            ],
            'description'   => 'nullable|string',
            'image'         => 'nullable|file|image|mimes:jpeg,png,jpg,webp',
            'status'        => 'nullable|integer',
            'businessId'    => 'sometimes|required|integer|exists:businesses,id',
            'categoryId'    => 'sometimes|required|integer|exists:main_categories,id',
            'subCategoryId'    => 'sometimes|required|integer|exists:sub_categories,id',
            'isAvailable'   => 'nullable|integer',
            'menuOrderId'   => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }
        $input = $request->except('image', 'removeImage'); // Get all except image

        // Handle image removal
        if ($request->has('removeImage') && $request->removeImage == '1') {
            // Delete old image if exists
            FileHelper::deleteImage($data->image);
            $input['image'] = null;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $folderName = $request->code ?? 'uploads';
            $destinationPath = public_path("images/$folderName");
            $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Optional: Delete old image
            FileHelper::deleteImage($data->image);

            $file->move($destinationPath, $fileName);

            $input['image'] = "images/$folderName/$fileName";
        }


        if (!$data->update(attributes: $input)) {
            return $this->sendError('Update failed', 'Could not update Item', 500);
        }

        return $this->sendResponse($data, 'Item updated successfully');
    }


    /**
     * @OA\Delete(
     *     path="/items/{id}",
     *     tags={"Item"},
     *     summary="Delete sub category (soft delete)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Item deleted successfully"),
     *     @OA\Response(response=404, description="Item not found")
     * )
     */

    public function destroy($id)
    {
        $data = Item::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Item not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Item deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/items/{id}/restore",
     *     tags={"Item"},
     *     summary="Restore soft deleted sub category",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub category restored successfully"),
     *     @OA\Response(response=404, description="Not found in trash")
     * )
     */

    public function restore($id)
    {
        $data = Item::onlyTrashed()->find($id);

        if (!$data) {
            return $this->sendError('Sub category not found in trash', [], 404);
        }

        $data->restore();

        return $this->sendResponse($data, 'Sub category restored successfully');
    }

    /**
     * @OA\Get(
     *     path="/items/trashed",
     *     tags={"Item"},
     *     summary="Get all soft-deleted items",
     *     @OA\Response(response=200, description="Trashed categories retrieved successfully"),
     *     @OA\Response(response=404, description="No trashed categories found")
     * )
     */

    public function trashed()
    {
        $data = Item::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed categories found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed categories retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/items/menu-order",
     *     tags={"Item"},
     *     summary="Update menu order for items",
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
        $existingIds = Item::whereIn('id', $entityIds)->pluck('id')->toArray();
        $missingIds = array_diff($entityIds, $existingIds);

        if (!empty($missingIds)) {
            return $this->sendError(
                'Entity not found',
                'The following item IDs do not exist: ' . implode(', ', $missingIds),
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
        $entities = Item::whereIn('id', $entityIds)->get();
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
                'attempted_item_ids' => $unauthorizedIds,
                'ip_address' => request()->ip()
            ]);

            return $this->sendError(
                'Authorization failed',
                'You do not have permission to modify these items. Item IDs: ' . implode(', ', $unauthorizedIds),
                403
            );
        }

        try {
            // Log transaction start
            Log::info("Item menu order update started", [
                'user_id' => $user->id,
                'business_id' => $userBusinessId,
                'entity_count' => count($updateData),
                'entity_ids' => $entityIds
            ]);

            DB::beginTransaction();
            
            foreach ($updateData as $value) {
                Item::where('id', $value['id'])->update([
                    'menuOrderId' => $value['menuOrderId'],
                ]);
            }

            DB::commit();

            // Log successful transaction
            Log::info("Item menu order update successful", [
                'user_id' => $user->id,
                'business_id' => $userBusinessId,
                'entity_count' => count($updateData)
            ]);

            return $this->sendResponse([], 'Menu order updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            
            // Log detailed error information
            Log::error("Item menu order update failed", [
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
