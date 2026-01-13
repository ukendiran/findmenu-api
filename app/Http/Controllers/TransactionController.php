<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends BaseController
{
    /**
     * Get list of transactions
     * GET /transactions
     */
    public function index(Request $request)
    {
        try {
            $query = Transaction::with(['business', 'subscription', 'payment']);

            if ($request->has('businessId')) {
                $query->where('businessId', $request->input('businessId'));
            }

            if ($request->has('subscriptionId')) {
                $query->where('subscriptionId', $request->input('subscriptionId'));
            }

            if ($request->has('paymentId')) {
                $query->where('paymentId', $request->input('paymentId'));
            }

            if ($request->has('transaction_type')) {
                $query->where('transaction_type', $request->input('transaction_type'));
            }

            if ($request->has('gateway')) {
                $query->where('gateway', $request->input('gateway'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->input('start_date'),
                    $request->input('end_date')
                ]);
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 50));

            return $this->sendResponse($transactions, 'Transactions retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Get a single transaction
     * GET /transactions/{id}
     */
    public function show($id)
    {
        try {
            $transaction = Transaction::with(['business', 'subscription', 'payment'])->find($id);
            
            if (!$transaction) {
                return $this->sendError('Not Found', 'Transaction not found', 404);
            }

            return $this->sendResponse($transaction, 'Transaction retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }

    /**
     * Get transaction summary for a business
     * GET /transactions/summary/{businessId}
     */
    public function getSummary($businessId)
    {
        try {
            $totalTransactions = Transaction::where('businessId', $businessId)->count();
            $successfulTransactions = Transaction::where('businessId', $businessId)
                ->where('status', 'success')
                ->count();
            $failedTransactions = Transaction::where('businessId', $businessId)
                ->where('status', 'failed')
                ->count();
            $totalAmount = Transaction::where('businessId', $businessId)
                ->where('status', 'success')
                ->sum('amount');

            $summary = [
                'total_transactions' => $totalTransactions,
                'successful_transactions' => $successfulTransactions,
                'failed_transactions' => $failedTransactions,
                'total_amount' => $totalAmount,
            ];

            return $this->sendResponse($summary, 'Transaction summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Server Error', $e->getMessage(), 500);
        }
    }
}
