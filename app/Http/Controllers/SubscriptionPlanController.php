<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends BaseController
{
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
}
