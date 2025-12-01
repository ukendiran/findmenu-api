<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AdminController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/admins",
     *     summary="Get list of admins",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="name", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="email", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="mobile", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="password", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Admin list retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No admins found"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $query = Admin::query();

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


        $data = $query->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No admins available', 404);
        }

        return $this->sendResponse($data, 'Admin list retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/admins",
     *     summary="Create a new Admin",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Admin")
     *     ),
     *     @OA\Response(response=200, description="Admin created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:6',
            'mobile' => 'nullable|string|max:20',
            'status' => 'nullable|integer',
            'image' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = $request->all();
        $data['password'] = Hash::make($data['password']);

        $data = Admin::create($data);
        $data->refresh();

        return $this->sendResponse($data, 'Admin created successfully');
    }

    /**
     * @OA\Get(
     *     path="/admins/{id}",
     *     summary="Get Admin by ID",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Admin retrieved successfully"),
     *     @OA\Response(response=404, description="Admin not found")
     * )
     */

    public function show($id)
    {
        $data = Admin::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'Admin not found', 404);
        }
        return $this->sendResponse($data, 'Admin retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/admins/{id}",
     *     summary="Update an existing Admin",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Admin")
     *     ),
     *     @OA\Response(response=200, description="Admin updated successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */

    public function update(Request $request, Admin $Admin)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:admins,email,' . $Admin->id,
            'password' => 'nullable|string|min:6',
            'mobile' => 'nullable|string|max:20',
            'status' => 'nullable|integer',
            'image' => 'nullable|string|max:100',
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

        $Admin->update($data);

        return $this->sendResponse($data, 'Admin updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/admins/{id}",
     *     summary="Delete Admin",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Admin deleted successfully"),
     *     @OA\Response(response=404, description="Admin not found")
     * )
     */

    public function destroy($id)
    {
        $data = Admin::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Admin not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Admin deleted successfully');
    }

}
