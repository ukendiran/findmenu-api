<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/users",
     *     summary="Get list of users",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="name", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="email", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="mobile", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="password", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="businessId", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="User list retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No users found"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $query = User::query();

        // Optional filters
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->has('email')) {
            $query->where('email', $request->input('email'));
        }
        if ($request->has('mobile')) {
            $query->where('mobile', $request->input('mobile'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('businessId')) {
            $query->where('businessId', $request->input('businessId'));
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No users available', 404);
        }

        return $this->sendResponse($data, 'User list retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/users",
     *     summary="Create a new user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=200, description="User created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:Male,Female,Other',
            'status' => 'nullable|integer',
            'profileImage' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:100',
            'businessId' => 'required|integer|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = $request->all();
        $data['password'] = Hash::make($data['password']);

        $data = User::create($data);
        $data->refresh();

        return $this->sendResponse($data, 'User created successfully');
    }

    /**
     * @OA\Get(
     *     path="/users/{id}",
     *     summary="Get user by ID",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="User retrieved successfully"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */

    public function show($id)
    {
        $data = User::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'User not found', 404);
        }
        return $this->sendResponse($data, 'User retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/users/{id}",
     *     summary="Update an existing user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=200, description="User updated successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'mobile' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:Male,Female,Other',
            'status' => 'nullable|integer',
            'profileImage' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:100',
            'businessId' => 'required|integer|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = $request->all();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']); // Prevent null overwriting password
        }

        $user->update($data);

        return $this->sendResponse($data, 'User updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/users/{id}",
     *     summary="Delete user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="User deleted successfully"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */

    public function destroy($id)
    {
        $data = User::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'User not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'User deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/users/{id}/restore",
     *     summary="Restore a soft-deleted user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="User restored successfully"),
     *     @OA\Response(response=404, description="User not found in trash")
     * )
     */

    public function restore($id)
    {
        $data = User::onlyTrashed()->find($id);

        if (!$data) {
            return $this->sendError('User not found in trash', [], 404);
        }

        $data->restore();

        return $this->sendResponse($data, 'User restored successfully');
    }

    /**
     * @OA\Get(
     *     path="/users/trashed",
     *     summary="Get all trashed (soft-deleted) users",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Trashed users retrieved successfully"),
     *     @OA\Response(response=404, description="No trashed users found")
     * )
     */

    public function trashed()
    {
        $data = User::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed users found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed users retrieved successfully');
    }
}
