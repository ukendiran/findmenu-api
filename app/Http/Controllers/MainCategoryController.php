<?php

namespace App\Http\Controllers;

use App\Models\MainCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;


class MainCategoryController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/main-categories",
     *     tags={"Main Category"},
     *     summary="Get list of main categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=false,
     *         description="Filter by main category name",
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
     *         name="businessId",
     *         in="query",
     *         required=false,
     *         description="Filter by business ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No data found"
     *     )
     * )
     */


    public function index(Request $request)
    {
        $query = MainCategory::query();

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
            return $this->sendError('No data found', 'No Categories available', 404);
        }

        return $this->sendResponse($data, 'Categories retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/main-categories",
     *     tags={"Main Category"},
     *     summary="Create a new main category",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/MainCategory")
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
                Rule::unique('main_categories')->where(function ($query) use ($request) {
                    return $query->where('businessId', $request->businessId);
                }),
            ],
            'description' => 'nullable|string',
            'image'       => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'      => 'nullable|integer',
            'businessId'  => 'sometimes|required|integer|exists:businesses,id',
            'isAvailable' => 'nullable|integer',
            'menuOrderId' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $input = $request->all();

        // Handle image upload
        if ($request->hasFile('image')) {
            $businessCode = MainCategory::where('id', $request->businessId)->value('code');
            $folderPath = 'images/' . $businessCode;

            $file = $request->file('image');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("{$folderPath}", $fileName);


            // Save relative path to DB
            $input['image'] =  $folderPath . '/' . $fileName;
        }

        $data = MainCategory::create($input);
        $data->refresh();

        return $this->sendResponse($data, 'Sub category created successfully');
    }

    /**
     * @OA\Get(
     *     path="/main-categories/{id}",
     *     tags={"Main Category"},
     *     summary="Get main category by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub category retrieved successfully"),
     *     @OA\Response(response=404, description="Sub category not found")
     * )
     */

    public function show($id)
    {
        $data = MainCategory::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'MainCategory not found', 404);
        }
        return $this->sendResponse($data, 'MainCategory retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/main-categories/{id}",
     *     tags={"Main Category"},
     *     summary="Update main category by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/MainCategory")
     *     ),
     *     @OA\Response(response=200, description="Sub category updated successfully"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */

    public function update(Request $request, $id)
    {
        $data = MainCategory::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'Main Category not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('main_categories', 'name')
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('businessId', $request->businessId);
                    })
                    ->ignore($data->id, 'id'),
            ],

            'description'   => 'nullable|string',
            'image'         => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'        => 'nullable|integer',
            'businessId'    => 'sometimes|required|integer|exists:businesses,id',
            'isAvailable'   => 'nullable|integer',
            'menuOrderId'   => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }
        $input = $request->except('image'); // Get all except image

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $folderName = $request->code ?? 'uploads';
            $destinationPath = public_path("images/$folderName");
            $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Optional: Delete old image
            if ($data->image && file_exists(public_path($data->image))) {
                unlink(public_path($data->image));
            }

            $file->move($destinationPath, $fileName);

            $input['image'] = "images/$folderName/$fileName";
        }

        if (!$data->update($input)) {
            return $this->sendError('Update failed', 'Could not update Sub category', 500);
        }

        return $this->sendResponse($data, 'Sub category updated successfully');
    }


    /**
     * @OA\Delete(
     *     path="/main-categories/{id}",
     *     tags={"Main Category"},
     *     summary="Delete main category (soft delete)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Main Category deleted successfully"),
     *     @OA\Response(response=404, description="Main Category not found")
     * )
     */

    public function destroy($id)
    {
        $data = MainCategory::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Main Category not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Main Category deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/main-categories/{id}/restore",
     *     tags={"Main Category"},
     *     summary="Restore soft deleted main category",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub category restored successfully"),
     *     @OA\Response(response=404, description="Not found in trash")
     * )
     */

    public function restore($id)
    {
        $data = MainCategory::onlyTrashed()->find($id);

        if (!$data) {
            return $this->sendError('Sub category not found in trash', [], 404);
        }

        $data->restore();

        return $this->sendResponse($data, 'Sub category restored successfully');
    }

    /**
     * @OA\Get(
     *     path="/main-categories/trashed",
     *     tags={"Main Category"},
     *     summary="Get all soft-deleted main categories",
     *     @OA\Response(response=200, description="Trashed categories retrieved successfully"),
     *     @OA\Response(response=404, description="No trashed categories found")
     * )
     */

    public function trashed()
    {
        $data = MainCategory::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed categories found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed categories retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/main-categories/menu-order",
     *     tags={"Main Category"},
     *     summary="Update menu order for main categories",
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
        $data = $request->all();

        try {
            DB::beginTransaction();
            foreach ($data['updateData'] as $value) {
                if (isset($value['id'], $value['menuOrderId'])) {
                    MainCategory::where('id', $value['id'])->update([
                        'menuOrderId' => $value['menuOrderId'],
                    ]);
                } else {
                    throw new Exception("Missing id or menuOrderId.");
                }
            }

            DB::commit();
            return $this->sendResponse([], 'Menu order updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("MainCategory menu order update failed: " . $e->getMessage());
            return $this->sendError('Failed to update menu order', $e->getMessage(), 500);
        }
    }
}
