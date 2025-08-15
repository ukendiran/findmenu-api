<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/notification",
     *     summary="Get list of notifications",
     *     tags={"Notification"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="message", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Notifications retrieved successfully"),
     *     @OA\Response(response=404, description="No data found")
     * )
     */
    public function index(Request $request)
    {
        $query = Notification::query();

        if ($request->has('message')) {
            $query->where('message', 'like', '%'.$request->input('message').'%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No notifications available', 404);
        }

        return $this->sendResponse($data, 'Notifications retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/notification",
     *     summary="Create a new notification",
     *     tags={"Notification"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/Notification")
     *     ),
     *
     *     @OA\Response(response=200, description="Notification created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'status' => 'required|integer',
            'businessId' => 'required|integer|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = Notification::create($request->all());
        $data->refresh();

        return $this->sendResponse($data, 'Notification created successfully');
    }

    /**
     * @OA\Get(
     *     path="/notification/{id}",
     *     summary="Get a notification by ID",
     *     tags={"Notification"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Notification retrieved successfully"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function show($id)
    {
        $data = Notification::find($id);
        if (! $data) {
            return $this->sendError('Not Found', 'Notification not found', 404);
        }

        return $this->sendResponse($data, 'Notification retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/notification/{id}",
     *     summary="Update a notification",
     *     tags={"Notification"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/Notification")
     *     ),
     *
     *     @OA\Response(response=200, description="Notification updated successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(Request $request, Notification $data)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string',
            'status' => 'nullable|integer',
            'businessId' => 'required|integer|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data->update($request->all());

        return $this->sendResponse($data, 'Notification updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/notification/{id}",
     *     summary="Delete a notification",
     *     tags={"Notification"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Notification deleted successfully"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function destroy($id)
    {
        $data = Notification::find($id);

        if (! $data) {
            return $this->sendError('Not Found', 'Notification not found', 404);
        }

        $data->delete();

        return $this->sendResponse(null, 'Notification deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/notification/restore/{id}",
     *     summary="Restore a deleted notification",
     *     tags={"Notification"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Notification restored successfully"),
     *     @OA\Response(response=404, description="Notification not found in trash")
     * )
     */
    public function restore($id)
    {
        $data = Notification::onlyTrashed()->find($id);

        if (! $data) {
            return $this->sendError('Notification not found in trash', [], 404);
        }

        $data->restore();

        return $this->sendResponse($data, 'Notification restored successfully');
    }

    /**
     * @OA\Get(
     *     path="/notification/trashed",
     *     summary="List all trashed notifications",
     *     tags={"Notification"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Trashed notifications retrieved successfully"),
     *     @OA\Response(response=404, description="No trashed notification found")
     * )
     */
    public function trashed()
    {
        $data = Notification::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed notification found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed notification retrieved successfully');
    }
}
