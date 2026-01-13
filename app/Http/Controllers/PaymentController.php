<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PaymentController extends BaseController
{
    /**
     * Initiate payment (gateway selection)
     * POST /payments/initiate
     */
    public function initiate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'planId' => 'required|exists:subscription_plans,id',
                'businessId' => 'required|exists:businesses,id',
                'gateway' => 'required|in:phonepe,razorpay,stripe',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            $gateway = $request->input('gateway');

            switch ($gateway) {
                case 'phonepe':
                    return $this->initiatePhonePe($request);
                case 'razorpay':
                    return $this->initiateRazorpay($request);
                case 'stripe':
                    return $this->initiateStripe($request);
                default:
                    return $this->sendError('Invalid gateway', 'Payment gateway not supported', 400);
            }
        } catch (\Exception $e) {
            Log::error('Payment Initiation Error:', ['error' => $e->getMessage()]);
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Initiate PhonePe payment
     * POST /payments/phonepe/initiate
     */
    public function initiatePhonePe(Request $request)
    {
        try {
            $validated = $request->validate([
                'planId' => 'required|exists:subscription_plans,id',
                'businessId' => 'required|exists:businesses,id'
            ]);

            $plan = SubscriptionPlan::find($validated['planId']);
            $business = Business::find($validated['businessId']);

            if (!$plan || !$business) {
                return $this->sendError('Invalid data', 'Plan or business not found', 404);
            }

            // Generate unique transaction ID
            $transactionId = 'TXN_' . Str::upper(Str::random(12));
            $amount = $plan->price * 100; // Convert to paise

            // PhonePe API configuration
            $merchantId = env('PHONEPE_MERCHANT_ID');
            $saltKey = env('PHONEPE_SALT_KEY');
            $saltIndex = env('PHONEPE_SALT_INDEX', 1);
            $baseUrl = env('PHONEPE_BASE_URL', 'https://api.phonepe.com/apis/hermes');

            // Prepare payload
            $payload = [
                "merchantId" => $merchantId,
                "merchantTransactionId" => $transactionId,
                "merchantUserId" => "MUID_" . $business->id,
                "amount" => $amount,
                "redirectUrl" => url('/api/payments/callback'),
                "redirectMode" => "REDIRECT",
                "callbackUrl" => url('/api/payments/callback'),
                "mobileNumber" => $business->mobile ?? $business->phone,
                "paymentInstrument" => [
                    "type" => "PAY_PAGE"
                ]
            ];

            // Encode payload
            $base64Payload = base64_encode(json_encode($payload));

            // Generate checksum
            $checksum = hash('sha256', $base64Payload . '/pg/v1/pay' . $saltKey) . '###' . $saltIndex;

            // Make API call to PhonePe
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
                'accept' => 'application/json',
            ])->post($baseUrl . '/pg/v1/pay', [
                'request' => $base64Payload
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                DB::beginTransaction();
                try {
                    // Create payment record
                    $payment = Payment::create([
                        'businessId' => $business->id,
                        'planId' => $plan->id,
                        'amount' => $plan->price,
                        'currency' => 'INR',
                        'gateway' => 'phonepe',
                        'gateway_transaction_id' => $transactionId,
                        'status' => 'pending',
                        'metadata' => [
                            'payload' => $payload,
                            'response' => $responseData,
                        ],
                    ]);

                    // Create transaction record
                    Transaction::create([
                        'businessId' => $business->id,
                        'paymentId' => $payment->id,
                        'transaction_type' => 'payment_initiated',
                        'amount' => $plan->price,
                        'currency' => 'INR',
                        'gateway' => 'phonepe',
                        'status' => 'pending',
                        'metadata' => [
                            'transaction_id' => $transactionId,
                            'plan_id' => $plan->id,
                            'plan_name' => $plan->name,
                        ],
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    DB::commit();

                    return $this->sendResponse([
                        'payment_url' => $responseData['data']['instrumentResponse']['redirectInfo']['url'] ?? null,
                        'transaction_id' => $transactionId,
                        'payment_id' => $payment->id
                    ], 'Payment initiated successfully');
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } else {
                Log::error('PhonePe API Error:', $responseData);
                return $this->sendError('Payment initiation failed', 'Unable to initiate payment with PhonePe', 500);
            }
        } catch (\Exception $e) {
            Log::error('PhonePe Payment Initiation Error:', ['error' => $e->getMessage()]);
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Initiate Razorpay payment
     * POST /payments/razorpay/initiate
     */
    public function initiateRazorpay(Request $request)
    {
        try {
            $validated = $request->validate([
                'planId' => 'required|exists:subscription_plans,id',
                'businessId' => 'required|exists:businesses,id'
            ]);

            $plan = SubscriptionPlan::find($validated['planId']);
            $business = Business::find($validated['businessId']);

            if (!$plan || !$business) {
                return $this->sendError('Invalid data', 'Plan or business not found', 404);
            }

            $keyId = env('RAZORPAY_KEY_ID');
            $keySecret = env('RAZORPAY_KEY_SECRET');

            if (!$keyId || !$keySecret) {
                return $this->sendError('Configuration error', 'Razorpay credentials not configured', 500);
            }

            // Generate unique order ID
            $orderId = 'ORDER_' . Str::upper(Str::random(12));
            $amount = $plan->price * 100; // Convert to paise

            // Create order via Razorpay API
            $response = Http::withBasicAuth($keyId, $keySecret)
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => $amount,
                    'currency' => 'INR',
                    'receipt' => $orderId,
                    'notes' => [
                        'business_id' => $business->id,
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                    ]
                ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['id'])) {
                DB::beginTransaction();
                try {
                    // Create payment record
                    $payment = Payment::create([
                        'businessId' => $business->id,
                        'planId' => $plan->id,
                        'amount' => $plan->price,
                        'currency' => 'INR',
                        'gateway' => 'razorpay',
                        'gateway_transaction_id' => $orderId,
                        'gateway_payment_id' => $responseData['id'],
                        'status' => 'pending',
                        'metadata' => [
                            'order_id' => $orderId,
                            'razorpay_order_id' => $responseData['id'],
                            'response' => $responseData,
                        ],
                    ]);

                    // Create transaction record
                    Transaction::create([
                        'businessId' => $business->id,
                        'paymentId' => $payment->id,
                        'transaction_type' => 'payment_initiated',
                        'amount' => $plan->price,
                        'currency' => 'INR',
                        'gateway' => 'razorpay',
                        'status' => 'pending',
                        'metadata' => [
                            'order_id' => $orderId,
                            'razorpay_order_id' => $responseData['id'],
                        ],
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    DB::commit();

                    return $this->sendResponse([
                        'order_id' => $responseData['id'],
                        'amount' => $amount,
                        'currency' => 'INR',
                        'key_id' => $keyId,
                        'payment_id' => $payment->id,
                    ], 'Razorpay payment initiated successfully');
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } else {
                Log::error('Razorpay API Error:', $responseData);
                return $this->sendError('Payment initiation failed', 'Unable to initiate payment with Razorpay', 500);
            }
        } catch (\Exception $e) {
            Log::error('Razorpay Payment Initiation Error:', ['error' => $e->getMessage()]);
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Initiate Stripe payment
     * POST /payments/stripe/initiate
     */
    public function initiateStripe(Request $request)
    {
        try {
            $validated = $request->validate([
                'planId' => 'required|exists:subscription_plans,id',
                'businessId' => 'required|exists:businesses,id'
            ]);

            $plan = SubscriptionPlan::find($validated['planId']);
            $business = Business::find($validated['businessId']);

            if (!$plan || !$business) {
                return $this->sendError('Invalid data', 'Plan or business not found', 404);
            }

            $secretKey = env('STRIPE_SECRET_KEY');

            if (!$secretKey) {
                return $this->sendError('Configuration error', 'Stripe credentials not configured', 500);
            }

            // Generate unique payment intent ID
            $paymentIntentId = 'PI_' . Str::upper(Str::random(12));
            $amount = (int)($plan->price * 100); // Convert to cents

            // Create payment intent via Stripe API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => $amount,
                'currency' => 'usd',
                'metadata' => [
                    'business_id' => $business->id,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                ],
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['id'])) {
                DB::beginTransaction();
                try {
                    // Create payment record
                    $payment = Payment::create([
                        'businessId' => $business->id,
                        'planId' => $plan->id,
                        'amount' => $plan->price,
                        'currency' => 'USD',
                        'gateway' => 'stripe',
                        'gateway_transaction_id' => $paymentIntentId,
                        'gateway_payment_id' => $responseData['id'],
                        'status' => 'pending',
                        'metadata' => [
                            'payment_intent_id' => $responseData['id'],
                            'client_secret' => $responseData['client_secret'],
                            'response' => $responseData,
                        ],
                    ]);

                    // Create transaction record
                    Transaction::create([
                        'businessId' => $business->id,
                        'paymentId' => $payment->id,
                        'transaction_type' => 'payment_initiated',
                        'amount' => $plan->price,
                        'currency' => 'USD',
                        'gateway' => 'stripe',
                        'status' => 'pending',
                        'metadata' => [
                            'payment_intent_id' => $responseData['id'],
                        ],
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    DB::commit();

                    return $this->sendResponse([
                        'payment_intent_id' => $responseData['id'],
                        'client_secret' => $responseData['client_secret'],
                        'amount' => $amount,
                        'currency' => 'usd',
                        'payment_id' => $payment->id,
                    ], 'Stripe payment initiated successfully');
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            } else {
                Log::error('Stripe API Error:', $responseData);
                return $this->sendError('Payment initiation failed', 'Unable to initiate payment with Stripe', 500);
            }
        } catch (\Exception $e) {
            Log::error('Stripe Payment Initiation Error:', ['error' => $e->getMessage()]);
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Unified payment callback
     * POST /payments/callback
     */
    public function callback(Request $request)
    {
        try {
            $response = $request->all();
            Log::info('Payment Callback:', $response);

            // Determine gateway from request
            $gateway = $this->detectGateway($response);
            
            if (!$gateway) {
                return response()->json(['success' => false, 'message' => 'Unable to detect payment gateway'], 400);
            }

            switch ($gateway) {
                case 'phonepe':
                    return $this->handlePhonePeCallback($request);
                case 'razorpay':
                    return $this->handleRazorpayCallback($request);
                case 'stripe':
                    return $this->handleStripeCallback($request);
                default:
                    return response()->json(['success' => false, 'message' => 'Unsupported gateway'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Payment Callback Error:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle PhonePe callback
     */
    private function handlePhonePeCallback(Request $request)
    {
        $response = $request->all();
        $transactionId = $response['transactionId'] ?? $response['merchantTransactionId'] ?? null;
        $status = $response['code'] ?? $response['status'] ?? null;

        if (!$transactionId) {
            return response()->json(['success' => false, 'message' => 'Transaction ID not found']);
        }

        $payment = Payment::where('gateway_transaction_id', $transactionId)
            ->where('gateway', 'phonepe')
            ->first();

        if (!$payment) {
            Log::error('Payment not found:', ['transactionId' => $transactionId]);
            return response()->json(['success' => false, 'message' => 'Payment not found']);
        }

        DB::beginTransaction();
        try {
            $paymentStatus = $this->mapPhonePeStatus($status);
            
            $payment->update([
                'status' => $paymentStatus,
                'gateway_payment_id' => $response['transactionId'] ?? null,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'callback_response' => $response,
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]),
            ]);

            // Create transaction record
            Transaction::create([
                'businessId' => $payment->businessId,
                'paymentId' => $payment->id,
                'transaction_type' => $paymentStatus === 'success' ? 'payment_success' : 'payment_failed',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'gateway' => 'phonepe',
                'status' => $paymentStatus,
                'metadata' => $response,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // If payment successful, create/update subscription
            if ($paymentStatus === 'success') {
                $this->createOrUpdateSubscription($payment);
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhonePe Callback Error:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Handle Razorpay callback
     */
    private function handleRazorpayCallback(Request $request)
    {
        // Razorpay webhook/callback handling
        // Implementation depends on Razorpay webhook structure
        return response()->json(['success' => true]);
    }

    /**
     * Handle Stripe callback
     */
    private function handleStripeCallback(Request $request)
    {
        // Stripe webhook/callback handling
        // Implementation depends on Stripe webhook structure
        return response()->json(['success' => true]);
    }

    /**
     * Detect payment gateway from callback response
     */
    private function detectGateway(array $response): ?string
    {
        if (isset($response['merchantTransactionId']) || isset($response['transactionId'])) {
            return 'phonepe';
        }
        if (isset($response['razorpay_order_id']) || isset($response['razorpay_payment_id'])) {
            return 'razorpay';
        }
        if (isset($response['payment_intent']) || isset($response['type']) && strpos($response['type'], 'payment_intent') !== false) {
            return 'stripe';
        }
        return null;
    }

    /**
     * Map PhonePe status to our status
     */
    private function mapPhonePeStatus($phonePeStatus): string
    {
        $statusMap = [
            'PAYMENT_SUCCESS' => 'success',
            'PAYMENT_ERROR' => 'failed',
            'PAYMENT_PENDING' => 'pending',
            'PAYMENT_DECLINED' => 'failed'
        ];

        return $statusMap[$phonePeStatus] ?? 'pending';
    }

    /**
     * Create or update subscription after successful payment
     */
    private function createOrUpdateSubscription(Payment $payment)
    {
        try {
            $plan = SubscriptionPlan::find($payment->planId);
            if (!$plan) {
                Log::error('Plan not found for payment:', ['payment_id' => $payment->id]);
                return;
            }

            // Check if business has a trial subscription
            $existingSubscription = Subscription::where('businessId', $payment->businessId)
                ->whereIn('status', [1, 4])
                ->first();

            if ($existingSubscription && $existingSubscription->status === 4) {
                // Convert trial to paid
                $endsAt = $this->calculateEndDate(Carbon::now(), $plan->billing_period);
                
                $existingSubscription->update([
                    'paymentId' => $payment->id,
                    'payment_gateway' => $payment->gateway,
                    'status' => 1, // Active
                    'ends_at' => $endsAt,
                    'trial_ends_at' => null,
                ]);

                // Create transaction for trial conversion
                Transaction::create([
                    'businessId' => $payment->businessId,
                    'subscriptionId' => $existingSubscription->id,
                    'paymentId' => $payment->id,
                    'transaction_type' => 'trial_converted',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'gateway' => $payment->gateway,
                    'status' => 'success',
                    'metadata' => [
                        'converted_at' => Carbon::now()->toDateTimeString(),
                    ],
                ]);
            } else {
                // Create new subscription
                $startsAt = Carbon::now();
                $endsAt = $this->calculateEndDate($startsAt, $plan->billing_period);

                $subscription = Subscription::create([
                    'businessId' => $payment->businessId,
                    'planId' => $payment->planId,
                    'paymentId' => $payment->id,
                    'payment_gateway' => $payment->gateway,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'trial_ends_at' => null,
                    'auto_renew' => false,
                    'status' => 1, // Active
                ]);

                // Create transaction
                Transaction::create([
                    'businessId' => $payment->businessId,
                    'subscriptionId' => $subscription->id,
                    'paymentId' => $payment->id,
                    'transaction_type' => 'subscription_created',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'gateway' => $payment->gateway,
                    'status' => 'success',
                    'metadata' => [
                        'created_at' => Carbon::now()->toDateTimeString(),
                    ],
                ]);
            }

            Log::info('Subscription created/updated for payment:', [
                'payment_id' => $payment->id,
                'business_id' => $payment->businessId,
            ]);
        } catch (\Exception $e) {
            Log::error('Subscription creation error:', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate end date based on billing period
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

    /**
     * Get payment history for a business
     * GET /payments/history/{businessId}
     */
    public function getHistory($businessId)
    {
        try {
            $payments = Payment::where('businessId', $businessId)
                ->with(['plan', 'subscription'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendResponse($payments, 'Payment history retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }
}
