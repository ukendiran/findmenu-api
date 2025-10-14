<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionPlanController extends BaseController
{



    public function index(Request $request)
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


    /**
     * Get available renewal plans
     * GET /plans-renew
     */
    public function getRenewalPlans(Request $request)
    {
        try {
            // Get all active plans that are available for renewal
            $plans = SubscriptionPlan::where('status', 1)
                ->where('is_renewable', true)
                ->orderBy('id', 'asc')
                ->get();

            if ($plans->isEmpty()) {
                return $this->sendError('No plans available', 'No renewal plans found', 404);
            }

            // Format the response with features as array
            $formattedPlans = $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => $plan->price,
                    'billing_period' => $plan->billing_period,
                    'features' => $plan->features ? json_decode($plan->features, true) : [],
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at
                ];
            });

            return $this->sendResponse($formattedPlans, 'Renewal plans retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
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
            'name' => 'required|string',
            'slug' => 'required|string',
            'billing_period' => 'required|integer',
            'price' => 'required|numeric',
            'status' => 'required|integer',
            'is_renewable' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = SubscriptionPlan::create($request->all());
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
        $data = SubscriptionPlan::find($id);
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
            'name' => 'required|string',
            'slug' => 'required|string',
            'billing_period' => 'required|integer',
            'price' => 'required|numeric',
            'status' => 'required|integer',
            'is_renewable' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data->update($request->all());
        $data->refresh();
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
        $data = SubscriptionPlan::find($id);

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
        $data = SubscriptionPlan::onlyTrashed()->find($id);

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
        $data = SubscriptionPlan::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed subscription found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed subscription retrieved successfully');
    }
}
