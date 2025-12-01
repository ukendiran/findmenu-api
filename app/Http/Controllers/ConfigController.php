<?php

namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Swagger\Components;

class ConfigController extends BaseController
{
    // GET /config
    /**
     * @OA\Get(
     *     path="/config",
     *     summary="Get list of config settings",
     *     tags={"Config"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="businessId",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Config list retrieved successfully"),
     *     @OA\Response(response=404, description="No data found")
     * )
     */

    public function index(Request $request)
    {
        $query = Config::query();

        // Optional filters

        if ($request->has('businessId')) {
            $query->where('businessId', $request->input('businessId'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No config available', 404);
        }

        return $this->sendResponse($data, 'Config list retrieved successfully');
    }

    // POST /config
    /**
     * @OA\Post(
     *     path="/config",
     *     summary="Create a new config",
     *     tags={"Config"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Config")
     *     ),
     *     @OA\Response(response=200, description="Config created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Add your validation rules here, example:
            'json' => 'nullable|json',
            'status' => 'required|integer',
            'businessId' => 'required|integer',
            'googleReviewStatus' => 'nullable|integer',
            'googleReview' => 'nullable|string',
            'wifiPassword' => 'nullable|string|max:20',
            'wifiPasswordStatus' => 'nullable|integer',
            'instagramStatus' => 'nullable|integer',
            'instagram' => 'nullable|string|max:255',
            'review' => 'nullable|integer',
            'reviewStatus' => 'nullable|integer',
            'stars' => 'nullable|string|max:10',
            'starsStatus' => 'nullable|integer',
            'googleMapStatus' => 'nullable|integer',
            'googleMap' => 'nullable|string',
            'showFeedbackFormStatus' => 'nullable|integer',
            'facebookStatus' => 'nullable|integer',
            'facebook' => 'nullable|string|max:255',
            'youtubeStatus' => 'nullable|integer',
            'youtube' => 'nullable|string|max:255',
            'whatsappStatus' => 'nullable|integer',
            'whatsapp' => 'nullable|string|max:255',
            'tripadvisor' => 'nullable|string|max:255',
            'tripadvisorStatus' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data = Config::create($request->all());
        $data->refresh();
        return $this->sendResponse($data, 'Config created successfully');
    }

    // GET /config/{id}
    /**
     * @OA\Get(
     *     path="/config/{id}",
     *     summary="Get a config by ID",
     *     tags={"Config"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Config retrieved successfully"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */

    public function show($id)
    {
        $data = Config::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Config not found', 404);
        }

        return $this->sendResponse($data, 'Config retrieved successfully');
    }

    // PUT/PATCH /config/{id}
    /**
     * @OA\Put(
     *     path="/config/{id}",
     *     summary="Update config by ID",
     *     tags={"Config"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Config")),
     *     @OA\Response(response=200, description="Config updated successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(Request $request, $id)
    {
        $data = Config::find($id);
        if (!$data) {
            return $this->sendError('Not Found', 'Config not found', 404);
        }
        // Validate the request data
        $validator = Validator::make($request->all(), [
            // Use same validation rules as store
            'json' => 'nullable|json',
            'status' => 'nullable|integer',
            'businessId' => 'nullable|integer',
            'googleReviewStatus' => 'nullable|integer',
            'googleReview' => 'nullable|string',
            'wifiPassword' => 'nullable|string|max:20',
            'wifiPasswordStatus' => 'nullable|integer',
            'instagramStatus' => 'nullable|integer',
            'instagram' => 'nullable|string|max:255',
            'review' => 'nullable|integer',
            'reviewStatus' => 'nullable|integer',
            'stars' => 'nullable|string|max:10',
            'starsStatus' => 'nullable|integer',
            'googleMapStatus' => 'nullable|integer',
            'googleMap' => 'nullable|string',
            'showFeedbackFormStatus' => 'nullable|integer',
            'facebookStatus' => 'nullable|integer',
            'facebook' => 'nullable|string|max:255',
            'youtubeStatus' => 'nullable|integer',
            'youtube' => 'nullable|string|max:255',
            'whatsappStatus' => 'nullable|integer',
            'whatsapp' => 'nullable|string|max:255',
            'tripadvisor' => 'nullable|string|max:255',
            'tripadvisorStatus' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $data->update($request->all());
        return $this->sendResponse($data, 'Config updated successfully');
    }

    // DELETE /config/{id}
    /**
     * @OA\Delete(
     *     path="/config/{id}",
     *     summary="Delete config by ID",
     *     tags={"Config"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Config deleted successfully"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy($id)
    {
        $data = Config::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Config not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Config deleted successfully');
    }

}
