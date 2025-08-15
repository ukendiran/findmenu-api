<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends BaseController
{
    // Get all payments for current user
    public function index(Request $request)
    {
        $query = Payment::query();

        if ($request->has('businessId')) {
            $query->where('businessId', $request->input('businessId'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->with(['transactions'])->orderBy('created_at', 'DESC')->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No subscriptions available', 404);
        }

        return $this->sendResponse($data, 'Subscriptions retrieved successfully');
    }

    // Create a new payment record
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'planId' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'gateway' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $payment = Payment::create([
            'businessId' => $request->businessId,
            'userId' => $request->userId,
            'planId' => $request->planId,
            'amount' => $request->amount,
            'currency' => $request->currency ?? 'rupee',
            'gateway' => $request->gateway,
            'status' => 'pending',
        ]);

        return response()->json($payment, 201);
    }

    // Update payment status
    public function updateStatus(Request $request, Payment $payment)
    {
        if ($payment->userId !== $request->userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded',
            'gateway_paymentId' => 'sometimes|string',
        ]);

        $payment->update([
            'status' => $request->status,
            'gateway_paymentId' => $request->gateway_paymentId ?? $payment->gateway_paymentId,
        ]);

        return response()->json($payment);
    }
}
