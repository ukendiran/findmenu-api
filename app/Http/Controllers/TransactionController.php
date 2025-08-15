<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends BaseController
{
    // Create a new transaction
    public function store(Request $request, Payment $payment)
    {
        if ($payment->userId !== $request->userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:payment,refund',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'status' => 'required|in:pending,success,failed',
            'description' => 'sometimes|string',
            'gateway_transactionId' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $transaction = $payment->transactions()->create([
            'type' => $request->type,
            'amount' => $request->amount,
            'currency' => $request->currency ?? $payment->currency,
            'status' => $request->status,
            'description' => $request->description,
            'gateway_transactionId' => $request->gateway_transactionId,
            'metadata' => $request->metadata,
        ]);

        return response()->json($transaction, 201);
    }

    // Update transaction status
    public function updateStatus(Request $request, Transaction $transaction)
    {
        if ($transaction->payment->userId !== $request->userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,success,failed',
        ]);

        $transaction->update(['status' => $request->status]);

        return response()->json($transaction);
    }
}
