<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Business;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionController extends BaseController
{
    /**
     * Get list of subscriptions
     * GET /subscriptions
     */
    public function index(Request $request)
    {
        try {
            $query = Subscription::with(['plan', 'business', 'payment']);

            if ($request->has('businessId')) {
                $query->where('businessId', $request->input('businessId'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $subscriptions = $query->orderBy('created_at', 'desc')->get();

            return $this->sendResponse($subscriptions, 'Subscriptions retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Get current active subscription for a business
     * GET /subscriptions/current
     */
    public function getCurrent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'businessId' => 'required|exists:businesses,id',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            $subscription = Subscription::where('businessId', $request->input('businessId'))
                ->whereIn('status', [1, 4]) // Active or Trial
                ->with(['plan', 'payment'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$subscription) {
                return $this->sendError('Not Found', 'No active subscription found', 404);
            }

            return $this->sendResponse($subscription, 'Current subscription retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Get a subscription by ID
     * GET /subscriptions/{id}
     */
    public function show($id)
    {
        try {
            $subscription = Subscription::with(['plan', 'business', 'payment'])->find($id);
            
            if (!$subscription) {
                return $this->sendError('Not Found', 'Subscription not found', 404);
            }

            return $this->sendResponse($subscription, 'Subscription retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Create a new subscription (with trial support)
     * POST /subscriptions
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'businessId' => 'required|exists:businesses,id',
                'planId' => 'required|exists:subscription_plans,id',
                'startTrial' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            $business = Business::find($request->input('businessId'));
            $plan = SubscriptionPlan::find($request->input('planId'));

            if (!$business || !$plan) {
                return $this->sendError('Invalid data', 'Business or plan not found', 404);
            }

            // Check if business already has an active subscription
            $existingSubscription = Subscription::where('businessId', $business->id)
                ->whereIn('status', [1, 4])
                ->first();

            if ($existingSubscription) {
                return $this->sendError('Subscription exists', 'Business already has an active subscription', 400);
            }

            DB::beginTransaction();

            try {
                $startTrial = $request->input('startTrial', false);
                $trialDays = $plan->trial_days ?? 0;

                // Calculate dates
                $startsAt = Carbon::now();
                $trialEndsAt = $startTrial && $trialDays > 0 ? $startsAt->copy()->addDays($trialDays) : null;
                
                // If trial, ends_at is trial_ends_at, otherwise calculate based on billing period
                if ($startTrial && $trialDays > 0) {
                    $endsAt = $trialEndsAt;
                    $status = 4; // Trial
                } else {
                    $endsAt = $this->calculateEndDate($startsAt, $plan->billing_period);
                    $status = 1; // Active
                }

                $subscription = Subscription::create([
                    'businessId' => $business->id,
                    'planId' => $plan->id,
                    'paymentId' => null,
                    'payment_gateway' => null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'trial_ends_at' => $trialEndsAt,
                    'auto_renew' => false,
                    'status' => $status,
                ]);

                // Create transaction record
                Transaction::create([
                    'businessId' => $business->id,
                    'subscriptionId' => $subscription->id,
                    'paymentId' => null,
                    'transaction_type' => $startTrial ? 'trial_started' : 'subscription_created',
                    'amount' => $startTrial ? 0 : $plan->price,
                    'currency' => 'INR',
                    'gateway' => null,
                    'status' => 'success',
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                        'trial_days' => $trialDays,
                        'billing_period' => $plan->billing_period,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                DB::commit();

                $subscription->load(['plan', 'business']);

                return $this->sendResponse($subscription, 'Subscription created successfully', 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Update a subscription
     * PUT /subscriptions/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $subscription = Subscription::find($id);
            
            if (!$subscription) {
                return $this->sendError('Not Found', 'Subscription not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'nullable|integer|in:1,2,3,4',
                'auto_renew' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            $subscription->update($request->only(['status', 'auto_renew']));

            return $this->sendResponse($subscription, 'Subscription updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a subscription
     * POST /subscriptions/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        try {
            $subscription = Subscription::find($id);
            
            if (!$subscription) {
                return $this->sendError('Not Found', 'Subscription not found', 404);
            }

            DB::beginTransaction();

            try {
                $subscription->update([
                    'status' => 3, // Cancelled
                    'auto_renew' => false,
                ]);

                // Create transaction record
                Transaction::create([
                    'businessId' => $subscription->businessId,
                    'subscriptionId' => $subscription->id,
                    'paymentId' => null,
                    'transaction_type' => 'subscription_cancelled',
                    'amount' => 0,
                    'currency' => 'INR',
                    'gateway' => null,
                    'status' => 'success',
                    'metadata' => [
                        'cancelled_at' => Carbon::now()->toDateTimeString(),
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                DB::commit();

                return $this->sendResponse($subscription, 'Subscription cancelled successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Renew a subscription
     * POST /subscriptions/{id}/renew
     */
    public function renew(Request $request, $id)
    {
        try {
            $subscription = Subscription::with('plan')->find($id);
            
            if (!$subscription) {
                return $this->sendError('Not Found', 'Subscription not found', 404);
            }

            $plan = $subscription->plan;
            if (!$plan || !$plan->is_renewable) {
                return $this->sendError('Not renewable', 'This subscription plan is not renewable', 400);
            }

            DB::beginTransaction();

            try {
                $newEndsAt = $this->calculateEndDate(Carbon::now(), $plan->billing_period);

                $subscription->update([
                    'starts_at' => Carbon::now(),
                    'ends_at' => $newEndsAt,
                    'status' => 1, // Active
                    'trial_ends_at' => null, // Clear trial if exists
                ]);

                // Create transaction record
                Transaction::create([
                    'businessId' => $subscription->businessId,
                    'subscriptionId' => $subscription->id,
                    'paymentId' => null,
                    'transaction_type' => 'subscription_renewed',
                    'amount' => $plan->price,
                    'currency' => 'INR',
                    'gateway' => null,
                    'status' => 'success',
                    'metadata' => [
                        'renewed_at' => Carbon::now()->toDateTimeString(),
                        'new_ends_at' => $newEndsAt->toDateTimeString(),
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                DB::commit();

                return $this->sendResponse($subscription, 'Subscription renewed successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Convert trial to paid subscription
     * POST /subscriptions/{id}/convert-trial
     */
    public function convertTrial(Request $request, $id)
    {
        try {
            $subscription = Subscription::with('plan')->find($id);
            
            if (!$subscription) {
                return $this->sendError('Not Found', 'Subscription not found', 404);
            }

            if ($subscription->status !== 4) {
                return $this->sendError('Invalid status', 'Subscription is not in trial status', 400);
            }

            $validator = Validator::make($request->all(), [
                'paymentId' => 'required|exists:payments,id',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            DB::beginTransaction();

            try {
                $plan = $subscription->plan;
                $newEndsAt = $this->calculateEndDate(Carbon::now(), $plan->billing_period);

                $subscription->update([
                    'paymentId' => $request->input('paymentId'),
                    'status' => 1, // Active
                    'ends_at' => $newEndsAt,
                    'trial_ends_at' => null,
                ]);

                // Create transaction record
                Transaction::create([
                    'businessId' => $subscription->businessId,
                    'subscriptionId' => $subscription->id,
                    'paymentId' => $request->input('paymentId'),
                    'transaction_type' => 'trial_converted',
                    'amount' => $plan->price,
                    'currency' => 'INR',
                    'gateway' => null,
                    'status' => 'success',
                    'metadata' => [
                        'converted_at' => Carbon::now()->toDateTimeString(),
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                DB::commit();

                return $this->sendResponse($subscription, 'Trial converted to paid subscription successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Helper method to calculate end date based on billing period
     */
    private function calculateEndDate(Carbon $startDate, string $billingPeriod): Carbon
    {
        switch ($billingPeriod) {
            case 'yearly':
                return $startDate->copy()->addYear();
            case 'monthly':
            default:
                return $startDate->copy()->addMonth();
        }
    }
}
