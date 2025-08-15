<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Item;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ItemController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/items",
     *     tags={"Item"},
     *     summary="Get list of items",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=false,
     *         description="Filter by sub category name",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status (1 = active, 0 = inactive)",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="categoryId",
     *         in="query",
     *         required=false,
     *         description="Filter by parent category ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="subCategoryId",
     *         in="query",
     *         required=false,
     *         description="Filter by parent sub category ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="businessId",
     *         in="query",
     *         required=false,
     *         description="Filter by business ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
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

        // Optional filters
        if ($request->has('name')) {
            $query->where('name', 'like', '%'.$request->input('name').'%');
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

        $data = $query->orderBy('menuOrderId')->get();

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
     *
     *     @OA\Parameter(name="name", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="categoryId", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subCategoryId", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Items retrieved successfully"),
     *     @OA\Response(response=404, description="No items found")
     * )
     */
    public function withCategory(Request $request)
    {
        $query = Item::query();

        // Optional filters
        if ($request->has('name')) {
            $query->where('name', 'like', '%'.$request->input('name').'%');
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
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/Item")
     *     ),
     *
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
                    return $query->where('businessId', $request->businessId)->where('categoryId', $request->categoryId);
                }),
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp',
            'status' => 'nullable|integer',
            'businessId' => 'sometimes|required|integer|exists:businesses,id',
            'categoryId' => 'sometimes|required|integer|exists:main_categories,id',
            'isAvailable' => 'nullable|integer',
            'menuOrderId' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        $input = $request->except('image'); // Get all except image
        // Handle new images
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $folderName = $request->code ?? 'uploads';
            $destinationPath = public_path("images/$folderName");
            $fileName = uniqid().'.'.$file->getClientOriginalExtension();

            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            $file->move($destinationPath, $fileName);

            $input['image'] = "images/$folderName/$fileName";
        }

        $item = Item::create($input);

        return response()->json([
            'success' => true,
            'data' => $item,
            'message' => 'Item created successfully',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/items/{id}",
     *     tags={"Item"},
     *     summary="Get sub category by ID",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Sub category retrieved successfully"),
     *     @OA\Response(response=404, description="Sub category not found")
     * )
     */
    public function show($id)
    {
        $data = Item::find($id);
        if (! $data) {
            return $this->sendError('Not Found', 'Item not found', 404);
        }

        return $this->sendResponse($data, 'Item retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/items/{id}",
     *     tags={"Item"},
     *     summary="Update sub category by ID",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/Item")
     *     ),
     *
     *     @OA\Response(response=200, description="Sub category updated successfully"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $data = Item::find($id);
        if (! $data) {
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
                            ->where('categoryId', $request->categoryId);
                    })
                    ->ignore($data->id, 'id'),
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|file|image|mimes:jpeg,png,jpg,webp',
            'status' => 'nullable|integer',
            'businessId' => 'sometimes|required|integer|exists:businesses,id',
            'categoryId' => 'sometimes|required|integer|exists:main_categories,id',
            'subCategoryId' => 'sometimes|required|integer|exists:sub_categories,id',
            'isAvailable' => 'nullable|integer',
            'menuOrderId' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }
        $input = $request->except('image'); // Get all except image

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $folderName = $request->code ?? 'uploads';
            $destinationPath = public_path("images/$folderName");
            $fileName = uniqid().'.'.$file->getClientOriginalExtension();

            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Optional: Delete old image
            if ($data->image && file_exists(public_path($data->image))) {
                unlink(public_path($data->image));
            }

            $file->move($destinationPath, $fileName);

            $input['image'] = "images/$folderName/$fileName";
        }

        if (! $data->update(attributes: $input)) {
            return $this->sendError('Update failed', 'Could not update Item', 500);
        }

        return $this->sendResponse($data, 'Item updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/items/{id}",
     *     tags={"Item"},
     *     summary="Delete sub category (soft delete)",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Item deleted successfully"),
     *     @OA\Response(response=404, description="Item not found")
     * )
     */
    public function destroy($id)
    {
        $data = Item::find($id);

        if (! $data) {
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
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Sub category restored successfully"),
     *     @OA\Response(response=404, description="Not found in trash")
     * )
     */
    public function restore($id)
    {
        $data = Item::onlyTrashed()->find($id);

        if (! $data) {
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
     *
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
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *                 type="object",
     *                 required={"id", "menuOrderId"},
     *
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="menuOrderId", type="integer", example=2)
     *             )
     *         )
     *     ),
     *
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
        $data = $request->all();

        try {
            DB::beginTransaction();
            foreach ($data['updateData'] as $value) {
                if (isset($value['id'], $value['menuOrderId'])) {
                    Item::where('id', $value['id'])->update([
                        'menuOrderId' => $value['menuOrderId'],
                    ]);
                } else {
                    throw new Exception('Missing id or menuOrderId.');
                }
            }

            DB::commit();

            return $this->sendResponse([], 'Menu order updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Item menu order update failed: '.$e->getMessage());

            return $this->sendError('Failed to update menu order', $e->getMessage(), 500);
        }
    }
}
