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
     *     @OA\Parameter(name="message", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="businessId", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notifications retrieved successfully"),
     *     @OA\Response(response=404, description="No data found")
     * )
     */
    public function index(Request $request)
    {
        $query = Notification::query();

        if ($request->has('message')) {
            $query->where('message', 'like', '%' . $request->input('message') . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('businessId')) {
            $query->where('businessId', $request->input('businessId'));
        }

        $query->orderBy('created_at', 'desc');

        $data = $query->get();

        // Return empty array instead of error if no data found
        // This allows frontend to handle empty states gracefully
        return $this->sendResponse($data, $data->isEmpty() ? 'No notifications found' : 'Notifications retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/notification",
     *     summary="Create a new notification",
     *     tags={"Notification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Notification")
     *     ),
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
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification retrieved successfully"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function show($id)
    {
        $data = Notification::find($id);
        if (!$data) {
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
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Notification")
     *     ),
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
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification deleted successfully"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function destroy($id)
    {
        $data = Notification::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Notification not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Notification deleted successfully');
    }

    /**
     * @OA\Put(
     *     path="/notifications/{id}/mark-read",
     *     summary="Mark notification as read",
     *     tags={"Notification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification marked as read successfully"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function markAsRead($id)
    {
        $data = Notification::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Notification not found', 404);
        }

        $data->update(['status' => 2]);
        return $this->sendResponse($data, 'Notification marked as read successfully');
    }

    /**
     * @OA\Put(
     *     path="/notifications/mark-all-read",
     *     summary="Mark all notifications as read for a business",
     *     tags={"Notification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="businessId", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="All notifications marked as read successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function markAllAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'businessId' => 'required|integer|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $updated = Notification::where('businessId', $request->businessId)
            ->where('status', 1)
            ->update(['status' => 2]);

        return $this->sendResponse(['updated' => $updated], "Marked {$updated} notification(s) as read successfully");
    }

}
