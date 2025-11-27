<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GroupsController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/groups",
     *     tags={"Groups"},
     *     summary="Get list of main group",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=false,
     *         description="Filter by group name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status (1 = active, 0 = inactive)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="businessId",
     *         in="query",
     *         required=false,
     *         description="Filter by business ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No data found"
     *     )
     * )
     */


    public function index(Request $request)
    {
        $query = Group::query();

        // Optional filters
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No Group available', 404);
        }

        return $this->sendResponse($data, 'Group retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/groups",
     *     tags={"Groups"},
     *     summary="Create a new group",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Groups")
     *     ),
     *     @OA\Response(response=200, description="Group created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'code' => [
                'required',
                'string',
                'max:100',
                'unique:businesses', // 👈 Add this line
            ],
            'description' => 'nullable|string',
            'logo'       => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'bannerImage'       => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'      => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $input = $request->all();

        // Handle image upload
        if ($request->hasFile('logo')) {
            $businessCode = Group::where('id', $request->businessId)->value('code');
            $folderPath = 'images/' . $businessCode;

            $file = $request->file('logo');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Save relative path to DB
            $input['logo'] =  $folderPath . '/' . $fileName;
        }

        // Handle image upload
        if ($request->hasFile('bannerImage')) {
            $businessCode = Group::where('id', $request->businessId)->value('code');
            $folderPath = 'images/' . $businessCode;

            $file = $request->file('bannerImage');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Save relative path to DB
            $input['bannerImage'] =  $folderPath . '/' . $fileName;
        }

        $data = Group::create($input);
        $data->refresh();

        return $this->sendResponse($data, 'Group created successfully');
    }

    /**
     * @OA\Get(
     *     path="/groups/{id}",
     *     tags={"Groups"},
     *     summary="Get group by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Group retrieved successfully"),
     *     @OA\Response(response=404, description="Group not found")
     * )
     */

    public function code($code)
    {
        $group = Group::with('businesses')->where('code', $code)->first();

        if (!$group) {
            return $this->sendError('Not Found', 'Group not found', 404);
        }

        return $this->sendResponse($group, 'Group retrieved successfully');
    }



    public function show($id)
    {
        $data = Group::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'Groups not found', 404);
        }
        return $this->sendResponse($data, 'Groups retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/groups/{id}",
     *     tags={"Groups"},
     *     summary="Update group by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Groups")
     *     ),
     *     @OA\Response(response=200, description="Group updated successfully"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */

    public function update(Request $request, $id)
    {
        $data = Group::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'Groups not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('groups')->ignore($id),
            ],
            'description'   => 'nullable|string',
            'logo'         => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'bannerImage'         => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'        => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }
        $input = $request->except('logo'); // Get all except image

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $folderName = 'groups';
            $destinationPath = public_path("images/$folderName");
            $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Optional: Delete old image
            if ($data->logo && file_exists(public_path($data->logo))) {
                unlink(public_path($data->logo));
            }

            $file->move($destinationPath, $fileName);

            $input['logo'] = "images/$folderName/$fileName";
        }
        if ($request->hasFile('bannerImage')) {
            $file = $request->file('bannerImage');
            $folderName = 'groups';
            $destinationPath = public_path("images/$folderName");
            $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Optional: Delete old image
            if ($data->bannerImage && file_exists(public_path($data->bannerImage))) {
                unlink(public_path($data->bannerImage));
            }

            $file->move($destinationPath, $fileName);

            $input['bannerImage'] = "images/$folderName/$fileName";
        }

        if (!$data->update($input)) {
            return $this->sendError('Update failed', 'Could not update Group', 500);
        }

        return $this->sendResponse($data, 'Group updated successfully');
    }


    /**
     * @OA\Delete(
     *     path="/groups/{id}",
     *     tags={"Groups"},
     *     summary="Delete group (soft delete)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Groups deleted successfully"),
     *     @OA\Response(response=404, description="Groups not found")
     * )
     */

    public function destroy($id)
    {
        $data = Group::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Groups not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Groups deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/groups/{id}/restore",
     *     tags={"Groups"},
     *     summary="Restore soft deleted group",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Group restored successfully"),
     *     @OA\Response(response=404, description="Not found in trash")
     * )
     */

    public function restore($id)
    {
        $data = Group::onlyTrashed()->find($id);

        if (!$data) {
            return $this->sendError('Group not found in trash', [], 404);
        }

        $data->restore();

        return $this->sendResponse($data, 'Group restored successfully');
    }

    /**
     * @OA\Get(
     *     path="/groups/trashed",
     *     tags={"Groups"},
     *     summary="Get all soft-deleted main group",
     *     @OA\Response(response=200, description="Trashed group retrieved successfully"),
     *     @OA\Response(response=404, description="No trashed group found")
     * )
     */

    public function trashed()
    {
        $data = Group::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed group found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed group retrieved successfully');
    }
}
