<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends BaseController
{
    /**
     * Initiate PhonePe payment
     * POST /phonepe/initiate
     */
    public function initiatePhonePePayment(Request $request)
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

            return $this->sendResponse([
                'transaction_id' => $transactionId,
            ], 'Payment initiated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('Payment Initiation Error:', ['error' => $e->getMessage()]);
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    public function initiatePhonePePayment1(Request $request)
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
                "redirectUrl" => url('/api/payment-callback'),
                "redirectMode" => "REDIRECT",
                "callbackUrl" => url('/api/payment-callback'),
                "mobileNumber" => $business->phone,
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

            if ($response->successful() && $responseData['success']) {
                // Create payment record
                $payment = Payment::create([
                    'business_id' => $business->id,
                    'plan_id' => $plan->id,
                    'amount' => $plan->price,
                    'transaction_id' => $transactionId,
                    'merchant_transaction_id' => $transactionId,
                    'payment_gateway' => 'phonepe',
                    'status' => 'PENDING',
                    'payload' => json_encode($payload),
                    'response' => json_encode($responseData)
                ]);

                return $this->sendResponse([
                    'payment_url' => $responseData['data']['instrumentResponse']['redirectInfo']['url'],
                    'transaction_id' => $transactionId,
                    'payment_id' => $payment->id
                ], 'Payment initiated successfully');
            } else {
                Log::error('PhonePe API Error:', $responseData);
                return $this->sendError('Payment initiation failed', 'Unable to initiate payment with PhonePe', 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('Payment Initiation Error:', ['error' => $e->getMessage()]);
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * PhonePe payment callback
     * POST /payment-callback
     */
    public function paymentCallback(Request $request)
    {
        try {
            $response = $request->all();
            Log::info('Payment Callback:', $response);

            // Verify checksum
            $checksum = $response['checksum'];
            $transactionId = $response['transactionId'];
            $status = $response['code'];

            // Find payment record
            $payment = Payment::where('transaction_id', $transactionId)->first();

            if (!$payment) {
                Log::error('Payment not found:', ['transactionId' => $transactionId]);
                return response()->json(['success' => false, 'message' => 'Payment not found']);
            }

            // Update payment status
            $payment->update([
                'status' => $this->mapPhonePeStatus($status),
                'gateway_response' => json_encode($response),
                'updated_at' => now()
            ]);

            // If payment successful, create subscription
            if ($status === 'PAYMENT_SUCCESS') {
                $this->createSubscription($payment);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Payment Callback Error:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function mapPhonePeStatus($phonePeStatus)
    {
        $statusMap = [
            'PAYMENT_SUCCESS' => 'SUCCESS',
            'PAYMENT_ERROR' => 'FAILED',
            'PAYMENT_PENDING' => 'PENDING',
            'PAYMENT_DECLINED' => 'FAILED'
        ];

        return $statusMap[$phonePeStatus] ?? 'PENDING';
    }

    private function createSubscription(Payment $payment)
    {
        try {
            // Calculate expiry date
            $plan = SubscriptionPlan::find($payment->plan_id);
            $expiresAt = now()->addDays($plan->duration_days);

            // Create or update subscription
            Subscription::updateOrCreate(
                [
                    'business_id' => $payment->business_id,
                    'plan_id' => $payment->plan_id
                ],
                [
                    'status' => 'active',
                    'expires_at' => $expiresAt,
                    'payment_id' => $payment->id,
                    'renewed_at' => now()
                ]
            );

            Log::info('Subscription created/updated for business:', [
                'business_id' => $payment->business_id,
                'plan_id' => $payment->plan_id
            ]);
        } catch (\Exception $e) {
            Log::error('Subscription creation error:', ['error' => $e->getMessage()]);
        }
    }
}
