<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/feedbacks",
     *     summary="Get list of feedback",
     *     tags={"Feedback"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="businessId", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="message", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Feedback list retrieved successfully"),
     * )
     */
    public function index(Request $request)
    {
        $query = Feedback::query();

        if ($request->has('message')) {
            $query->where('message', 'like', '%' . $request->input('message') . '%');
        }

        if ($request->has('businessId')) {
            $query->where('businessId', $request->input('businessId'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->get();
        return $this->sendResponse($data, 'Feedback list retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/feedbacks",
     *     summary="Create feedback",
     *     tags={"Feedback"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Feedback")
     *     ),
     *     @OA\Response(response=200, description="Feedback created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'status' => 'nullable|integer',
            'businessId' => 'nullable|integer'
        ]);
        $data =  Feedback::create($data);
        $data->refresh();
        return $this->sendResponse($data, 'Feedback created successfully');
    }

    /**
     * @OA\Get(
     *     path="/feedbacks/{id}",
     *     summary="Get feedback by ID",
     *     tags={"Feedback"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Feedback retrieved successfully"),
     *     @OA\Response(response=404, description="Feedback not found")
     * )
     */
    public function show($id)
    {
        $data = Feedback::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'Feedback not found', 404);
        }
        return $this->sendResponse($data, 'Feedback retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/feedbacks/{id}",
     *     summary="Update feedback by ID",
     *     tags={"Feedback"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Feedback")
     *     ),
     *     @OA\Response(response=200, description="Feedback updated successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(Request $request, Feedback $data)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'sometimes|string',
            'status' => 'nullable|integer',
            'businessId' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data->update($request->all());
        return $this->sendResponse($data, 'Feedback updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/feedbacks/{id}",
     *     summary="Delete feedback by ID",
     *     tags={"Feedback"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Feedback deleted successfully"),
     *     @OA\Response(response=404, description="Feedback not found")
     * )
     */
    public function destroy($id)
    {
        $data = Feedback::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Feedback not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Feedback deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/feedbacks/restore/{id}",
     *     summary="Restore a deleted feedback",
     *     tags={"Feedback"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Feedback restored successfully"),
     *     @OA\Response(response=404, description="Feedback not found in trash")
     * )
     */
    public function restore($id)
    {
        $data = Feedback::onlyTrashed()->find($id);

        if (!$data) {
            return $this->sendError('Feedback not found in trash', [], 404);
        }

        $data->restore();

        return $this->sendResponse($data, 'Feedback restored successfully');
    }

    /**
     * @OA\Get(
     *     path="/feedbacks/trashed",
     *     summary="List all trashed feedbacks",
     *     tags={"Feedback"},
     *     @OA\Response(response=200, description="Trashed feedback retrieved successfully"),
     *     @OA\Response(response=404, description="No trashed feedback found")
     * )
     */
    public function trashed()
    {
        $data = Feedback::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed feedback found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed feedback retrieved successfully');
    }
}
