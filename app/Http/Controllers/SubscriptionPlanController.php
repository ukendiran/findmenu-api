<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionPlanController extends BaseController
{
    /**
     * Get all subscription plans (public endpoint)
     * GET /subscription-plans
     */
    public function index(Request $request)
    {
        try {
            $query = SubscriptionPlan::query();

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('slug')) {
                $query->where('slug', $request->input('slug'));
            }

            $plans = $query->orderBy('price', 'asc')->get();

            return $this->sendResponse($plans, 'Subscription plans retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Get a single subscription plan
     * GET /subscription-plans/{id}
     */
    public function show($id)
    {
        try {
            $plan = SubscriptionPlan::find($id);
            
            if (!$plan) {
                return $this->sendError('Not Found', 'Subscription plan not found', 404);
            }

            return $this->sendResponse($plan, 'Subscription plan retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Create a new subscription plan
     * POST /subscription-plans
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|unique:subscription_plans,slug',
                'price' => 'required|numeric|min:0',
                'billing_period' => 'required|string|in:monthly,yearly',
                'features' => 'nullable|array',
                'trial_days' => 'nullable|integer|min:0',
                'payment_gateways' => 'nullable|array',
                'status' => 'nullable|integer|in:0,1',
                'is_renewable' => 'nullable|boolean',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            $plan = SubscriptionPlan::create($request->all());

            return $this->sendResponse($plan, 'Subscription plan created successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Update a subscription plan
     * PUT /subscription-plans/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $plan = SubscriptionPlan::find($id);
            
            if (!$plan) {
                return $this->sendError('Not Found', 'Subscription plan not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|required|string|unique:subscription_plans,slug,' . $id,
                'price' => 'sometimes|required|numeric|min:0',
                'billing_period' => 'sometimes|required|string|in:monthly,yearly',
                'features' => 'nullable|array',
                'trial_days' => 'nullable|integer|min:0',
                'payment_gateways' => 'nullable|array',
                'status' => 'nullable|integer|in:0,1',
                'is_renewable' => 'nullable|boolean',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            $plan->update($request->all());

            return $this->sendResponse($plan, 'Subscription plan updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Delete a subscription plan
     * DELETE /subscription-plans/{id}
     */
    public function destroy($id)
    {
        try {
            $plan = SubscriptionPlan::find($id);
            
            if (!$plan) {
                return $this->sendError('Not Found', 'Subscription plan not found', 404);
            }

            $plan->delete();

            return $this->sendResponse(null, 'Subscription plan deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
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
                ->where('slug', '!=', 'trial')
                ->orderBy('price', 'asc')
                ->get();

            if ($plans->isEmpty()) {
                return $this->sendError('No plans available', 'No renewal plans found', 404);
            }

            return $this->sendResponse($plans, 'Renewal plans retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }
}
