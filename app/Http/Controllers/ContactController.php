<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends BaseController
{
    // GET /contact
    /**
     * @OA\Get(
     *     path="/contact",
     *     summary="Get list of contacts",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="name", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="message", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="email", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="mobile", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="businessId", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Contact list retrieved successfully"),
     *     @OA\Response(response=404, description="No data found")
     * )
     */
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->has('message')) {
            $query->where('message', 'like', '%' . $request->input('message') . '%');
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
            return $this->sendError('No data found', 'No contacts available', 404);
        }
        return $this->sendResponse($data, 'Contact list retrieved successfully');
    }

    // POST /contact
    /**
     * @OA\Post(
     *     path="/contact",
     *     summary="Create a new contact",
     *     tags={"Contact"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Contact")
     *     ),
     *     @OA\Response(response=200, description="Contact created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email',
            'mobile'  => 'nullable|string|max:20',
            'address' => 'nullable|string',
            // Add other validation rules as needed
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = Contact::create($request->all());
        $data->refresh();
        return $this->sendResponse($data, 'Contact created successfully');
    }

    // GET /contact/{id}
    /**
     * @OA\Get(
     *     path="/contact/{id}",
     *     summary="Get a contact by ID",
     *     tags={"Contact"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Contact retrieved successfully"),
     *     @OA\Response(response=404, description="Contact not found")
     * )
     */
    public function show($id)
    {
        $data = Contact::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Contact not found', 404);
        }

        return $this->sendResponse($data, 'Contact retrieved successfully');
    }


    // PUT/PATCH /contact/{id}
    /**
     * @OA\Put(
     *     path="/contact/{id}",
     *     summary="Update contact by ID",
     *     tags={"Contact"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Contact")),
     *     @OA\Response(response=200, description="Contact updated successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */    public function update(Request $request, Contact $data)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'sometimes|required|string|max:255',
            'email'   => 'nullable|email',
            'mobile'  => 'nullable|string|max:20',
            'address' => 'nullable|string',
            // Add other validation rules
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data->update($request->all());
        return $this->sendResponse($data, 'Contact updated successfully');
    }

    // DELETE /contact/{id}
    /**
     * @OA\Delete(
     *     path="/contact/{id}",
     *     summary="Delete contact by ID",
     *     tags={"Contact"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Contact deleted successfully"),
     *     @OA\Response(response=404, description="Contact not found")
     * )
     */
    public function destroy($id)
    {
        $data = Contact::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Contact not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Contact deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/contact/restore/{id}",
     *     summary="Restore a deleted contact",
     *     tags={"Contact"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Contact restored successfully"),
     *     @OA\Response(response=404, description="Contact not found in trash")
     * )
     */
    public function restore($id)
    {
        $data = Contact::onlyTrashed()->find($id);

        if (!$data) {
            return $this->sendError('Contact not found in trash', [], 404);
        }

        $data->restore();

        return $this->sendResponse($data, 'Contact restored successfully');
    }

    /**
     * @OA\Get(
     *     path="/contact/trashed",
     *     summary="List all trashed contacts",
     *     tags={"Contact"},
     *     @OA\Response(response=200, description="Trashed contacts retrieved successfully"),
     *     @OA\Response(response=404, description="No trashed contacts found")
     * )
     */
    public function trashed()
    {
        $data = Contact::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed contacts found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed contacts retrieved successfully');
    }
}
