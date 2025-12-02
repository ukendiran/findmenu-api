<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use App\Models\BusinessTypeField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessTypeFieldController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/business-types/{businessTypeId}/fields",
     *     summary="Get fields for a business type",
     *     tags={"Business Type Field"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="businessTypeId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Fields retrieved successfully"),
     *     @OA\Response(response=404, description="Business type not found")
     * )
     */
    public function index($businessTypeId)
    {
        $businessType = BusinessType::find($businessTypeId);
        
        if (!$businessType) {
            return $this->sendError('Not Found', 'Business type not found', 404);
        }

        $data = $businessType->fields()->ordered()->get();
        return $this->sendResponse($data, 'Fields retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/business-types/{businessTypeId}/fields",
     *     summary="Create field for business type",
     *     tags={"Business Type Field"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="businessTypeId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"field_name", "field_label", "field_type"},
     *             @OA\Property(property="field_name", type="string", example="cuisine_type"),
     *             @OA\Property(property="field_label", type="string", example="Cuisine Type"),
     *             @OA\Property(property="field_type", type="string", enum={"text", "number", "date", "boolean"}, example="text"),
     *             @OA\Property(property="is_required", type="boolean", example=false),
     *             @OA\Property(property="default_value", type="string", example=""),
     *             @OA\Property(property="order", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Field created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(Request $request, $businessTypeId)
    {
        $businessType = BusinessType::find($businessTypeId);
        
        if (!$businessType) {
            return $this->sendError('Not Found', 'Business type not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'field_name' => 'required|string|max:255',
            'field_label' => 'required|string|max:255',
            'field_type' => 'required|string|in:text,number,date,boolean',
            'is_required' => 'nullable|boolean',
            'default_value' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = $businessType->fields()->create(array_merge($request->all(), [
            'business_type_id' => $businessTypeId,
        ]));

        return $this->sendResponse($data, 'Field created successfully');
    }

    /**
     * @OA\Put(
     *     path="/business-type-fields/{id}",
     *     summary="Update field by ID",
     *     tags={"Business Type Field"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="field_name", type="string", example="cuisine_type"),
     *             @OA\Property(property="field_label", type="string", example="Cuisine Type"),
     *             @OA\Property(property="field_type", type="string", enum={"text", "number", "date", "boolean"}, example="text"),
     *             @OA\Property(property="is_required", type="boolean", example=false),
     *             @OA\Property(property="default_value", type="string", example=""),
     *             @OA\Property(property="order", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Field updated successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Field not found")
     * )
     */
    public function update(Request $request, $id)
    {
        $data = BusinessTypeField::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Field not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'field_name' => 'sometimes|required|string|max:255',
            'field_label' => 'sometimes|required|string|max:255',
            'field_type' => 'sometimes|required|string|in:text,number,date,boolean',
            'is_required' => 'nullable|boolean',
            'default_value' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data->update($request->all());

        return $this->sendResponse($data, 'Field updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/business-type-fields/{id}",
     *     summary="Delete field by ID",
     *     tags={"Business Type Field"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Field deleted successfully"),
     *     @OA\Response(response=404, description="Field not found")
     * )
     */
    public function destroy($id)
    {
        $data = BusinessTypeField::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Field not found', 404);
        }

        $data->delete();

        return $this->sendResponse(null, 'Field deleted successfully');
    }
}
