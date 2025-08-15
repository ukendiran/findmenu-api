<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends BaseController
{
    public function index(Request $request)
    {
        $query = Subscription::query();

        if ($request->has('businessId')) {
            $query->where('businessId', $request->input('businessId'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->with(['plan', 'business'])->orderBy('starts_at', 'DESC')->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No subscriptions available', 404);
        }

        return $this->sendResponse($data, 'Subscriptions retrieved successfully');
    }

    // List available plans
    public function plans()
    {
        $plans = SubscriptionPlan::where('status', 1)->get();

        return response()->json(['plans' => $plans]);
    }

    // Subscribe business to a plan (simplified, assumes payment successful)
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'planId' => 'required|exists:subscription_plans,id',
            'businessId' => 'required|exists:businesses,id',
            'paymentId' => 'nullable|string',
            'payment_gateway' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $plan = SubscriptionPlan::findOrFail($request->planId);
        $business = Business::findOrFail($request->businessId);

        // Deactivate old subscriptions
        Subscription::where('businessId', $business->id)->update(['is_active' => false]);

        // Dates
        $startsAt = now();
        $endsAt = match ($plan->billing_period) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };

        $subscription = Subscription::create([
            'businessId' => $business->id,
            'planId' => $plan->id,
            'payment_gateway' => $request->payment_gateway ?? 'razorpay',
            'paymentId' => $request->paymentId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Subscribed successfully',
            'subscription' => $subscription->load('plan'),
        ]);
    }

    // Get current business's active subscription
    public function mySubscription(Request $request)
    {
        $businessId = $request->query('businessId');

        $subscription = Subscription::where('businessId', $businessId)
            ->where('status', 1)
            ->with('plan')
            ->get();

        if (! $subscription) {
            return $this->sendError('No active subscription found', 'No subscription available', 404);
        }

        return $this->sendResponse($subscription, 'Subscription retrieved successfully');
    }

    public function mySubscriptions(Request $request)
    {
        $businessId = $request->query('businessId');

        $subscription = Subscription::where('businessId', $businessId)
            ->where('status', 1)
            ->whereDate('ends_at', '>=', now())
            ->with('plan')
            ->first();

        if (! $subscription) {
            return $this->sendError('No active subscription found', 'No subscription available', 404);
        }

        return $this->sendResponse($subscription, 'Subscription retrieved successfully');
    }
}
