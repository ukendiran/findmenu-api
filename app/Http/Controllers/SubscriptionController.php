<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class SubscriptionController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/subscription",
     *     summary="Get list of subscriptions",
     *     tags={"Subscription"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="message", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Subscriptions retrieved successfully"),
     *     @OA\Response(response=404, description="No data found")
     * )
     */
    public function index(Request $request)
    {
        $query = Subscription::query();

        $query->with('plan');

        if ($request->has('businessId')) {
            $query->where('businessId', $request->input('businessId'));
        }

        if ($request->has('message')) {
            $query->where('message', 'like', '%' . $request->input('message') . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No subscriptions available', 404);
        }

        return $this->sendResponse($data, 'Subscriptions retrieved successfully');
    }

    public function plans(Request $request)
    {
        $query = SubscriptionPlan::query();

        // $query->with('plan');

        // if ($request->has('slug')) {
        //     $query->where('slug', 'like', '%' . $request->input('message') . '%');
        // }

        if ($request->has('slug')) {
            $query->where('slug', $request->input('slug'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No subscriptions available', 404);
        }

        return $this->sendResponse($data, 'Subscriptions retrieved successfully');
    }

    public function plansRenew(Request $request)
    {
        $query = SubscriptionPlan::query();

        // $query->with('plan');

        // if ($request->has('slug')) {
        //     $query->where('slug', 'like', '%' . $request->input('message') . '%');
        // }


        $query->where('slug', '!=', 'trial');


        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No subscriptions available', 404);
        }

        return $this->sendResponse($data, 'Subscriptions retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/subscription",
     *     summary="Create a new subscription",
     *     tags={"Subscription"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Subscription")
     *     ),
     *     @OA\Response(response=200, description="Subscription created successfully"),
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

        $data = Subscription::create($request->all());
        $data->refresh();

        return $this->sendResponse($data, 'Subscription created successfully');
    }

    /**
     * @OA\Get(
     *     path="/subscription/{id}",
     *     summary="Get a subscription by ID",
     *     tags={"Subscription"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Subscription retrieved successfully"),
     *     @OA\Response(response=404, description="Subscription not found")
     * )
     */
    public function show($id)
    {
        $query = Subscription::query();
        $query->with('plan');
        $data = $query->find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Subscription not found', 404);
        }
        return $this->sendResponse($data, 'Subscription retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/subscription/{id}",
     *     summary="Update a subscription",
     *     tags={"Subscription"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Subscription")
     *     ),
     *     @OA\Response(response=200, description="Subscription updated successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(Request $request, Subscription $data)
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
        return $this->sendResponse($data, 'Subscription updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/subscription/{id}",
     *     summary="Delete a subscription",
     *     tags={"Subscription"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Subscription deleted successfully"),
     *     @OA\Response(response=404, description="Subscription not found")
     * )
     */
    public function destroy($id)
    {
        $data = Subscription::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Subscription not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Subscription deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/subscription/restore/{id}",
     *     summary="Restore a deleted subscription",
     *     tags={"Subscription"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Subscription restored successfully"),
     *     @OA\Response(response=404, description="Subscription not found in trash")
     * )
     */
    public function restore($id)
    {
        $data = Subscription::onlyTrashed()->find($id);

        if (!$data) {
            return $this->sendError('Subscription not found in trash', [], 404);
        }

        $data->restore();

        return $this->sendResponse($data, 'Subscription restored successfully');
    }

    /**
     * @OA\Get(
     *     path="/subscription/trashed",
     *     summary="List all trashed subscriptions",
     *     tags={"Subscription"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Trashed subscriptions retrieved successfully"),
     *     @OA\Response(response=404, description="No trashed subscription found")
     * )
     */
    public function trashed()
    {
        $data = Subscription::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed subscription found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed subscription retrieved successfully');
    }
}
