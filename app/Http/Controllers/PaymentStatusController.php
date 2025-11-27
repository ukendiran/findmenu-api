<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Payment;

class PaymentStatusController extends BaseController
{
    /**
     * Check payment status for business
     * GET /payment-status/{businessId}
     */
    public function getPaymentStatus($businessId)
    {
        try {
            // Validate business exists
            $business = Business::find($businessId);
            if (!$business) {
                return $this->sendError('Business not found', 'Invalid business ID', 404);
            }

            // Get latest payment for this business
            $latestPayment = Payment::where('business_id', $businessId)
                ->latest('created_at')
                ->first();

            if (!$latestPayment) {
                return $this->sendResponse([
                    'status' => 'NO_PAYMENTS',
                    'message' => 'No payment records found for this business'
                ], 'Payment status retrieved');
            }

            $response = [
                'status' => $latestPayment->status,
                'transaction_id' => $latestPayment->transaction_id,
                'amount' => $latestPayment->amount,
                'plan_id' => $latestPayment->plan_id,
                'created_at' => $latestPayment->created_at,
                'updated_at' => $latestPayment->updated_at
            ];

            // Add additional info if payment was successful
            if ($latestPayment->status === 'SUCCESS') {
                $response['subscription_active'] = true;
                $response['message'] = 'Payment successful - Subscription active';
            } elseif ($latestPayment->status === 'FAILED') {
                $response['message'] = 'Payment failed - Please try again';
            } else {
                $response['message'] = 'Payment pending - Please complete the payment';
            }

            return $this->sendResponse($response, 'Payment status retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Get detailed payment history for business
     * GET /payment-history/{businessId}
     */
    public function getPaymentHistory($businessId)
    {
        try {
            $business = Business::find($businessId);
            if (!$business) {
                return $this->sendError('Business not found', 'Invalid business ID', 404);
            }

            $payments = Payment::where('business_id', $businessId)
                ->with('plan')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'plan_name' => $payment->plan->name,
                        'payment_gateway' => $payment->payment_gateway,
                        'created_at' => $payment->created_at,
                        'updated_at' => $payment->updated_at
                    ];
                });

            return $this->sendResponse($payments, 'Payment history retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }
}
