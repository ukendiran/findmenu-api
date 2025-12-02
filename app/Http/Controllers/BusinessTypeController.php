<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessTypeController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/business-types",
     *     summary="Get list of business types",
     *     tags={"Business Type"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Business types retrieved successfully"),
     * )
     */
    public function index(Request $request)
    {
        $query = BusinessType::with('fields');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->get();
        return $this->sendResponse($data, 'Business types retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/business-types",
     *     summary="Create business type",
     *     tags={"Business Type"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Restaurant"),
     *             @OA\Property(property="description", type="string", example="Restaurant business type"),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Business type created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:business_types,name',
            'description' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = BusinessType::create($request->all());
        $data->load('fields');

        return $this->sendResponse($data, 'Business type created successfully');
    }

    /**
     * @OA\Get(
     *     path="/business-types/{id}",
     *     summary="Get business type by ID",
     *     tags={"Business Type"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Business type retrieved successfully"),
     *     @OA\Response(response=404, description="Business type not found")
     * )
     */
    public function show($id)
    {
        $data = BusinessType::with('fields')->find($id);
        
        if (!$data) {
            return $this->sendError('Not Found', 'Business type not found', 404);
        }

        return $this->sendResponse($data, 'Business type retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/business-types/{id}",
     *     summary="Update business type by ID",
     *     tags={"Business Type"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Restaurant"),
     *             @OA\Property(property="description", type="string", example="Restaurant business type"),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Business type updated successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Business type not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $data = BusinessType::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Business type not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:business_types,name,' . $id,
            'description' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data->update($request->all());
        $data->load('fields');

        return $this->sendResponse($data, 'Business type updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/business-types/{id}",
     *     summary="Delete business type by ID",
     *     tags={"Business Type"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Business type deleted successfully"),
     *     @OA\Response(response=404, description="Business type not found")
     * )
     */
    public function destroy($id)
    {
        $data = BusinessType::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Business type not found', 404);
        }

        $data->delete();

        return $this->sendResponse(null, 'Business type deleted successfully');
    }
}
