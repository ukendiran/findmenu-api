<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubscriptionPlanController extends BaseController
{
    public function index(Request $request)
    {
        $query = SubscriptionPlan::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No Subscription Plan available', 404);
        }

        return $this->sendResponse($data, 'Subscription Plan retrieved successfully');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subscriptionPlans')->where(function ($query) use ($request) {
                    return $query->where('businessId', $request->businessId)->where('categoryId', $request->categoryId);
                }),
            ],
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'businessId' => 'sometimes|required|integer|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        $input = $request->except('image'); // Get all except image

        $subscriptionPlan = SubscriptionPlan::create($input);

        return response()->json([
            'success' => true,
            'data' => $subscriptionPlan,
            'message' => 'Subscription Plan created successfully',
        ]);
    }

    public function show($id)
    {
        $data = SubscriptionPlan::find($id);
        if (! $data) {
            return $this->sendError('Not Found', 'Subscription Plan not found', 404);
        }

        return $this->sendResponse($data, 'Subscription Plan retrieved successfully');
    }

    public function update(Request $request, $id)
    {
        $data = SubscriptionPlan::find($id);
        if (! $data) {
            return $this->sendError('Not Found', 'Subscription Plan not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('items', 'name')
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('businessId', $request->businessId)
                            ->where('categoryId', $request->categoryId);
                    })
                    ->ignore($data->id, 'id'),
            ],
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'businessId' => 'sometimes|required|integer|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $input = $request->except('image'); // Get all except image

        if (! $data->update(attributes: $input)) {
            return $this->sendError('Update failed', 'Could not update Subscription Plan', 500);
        }

        return $this->sendResponse($data, 'Subscription Plan updated successfully');
    }
}
