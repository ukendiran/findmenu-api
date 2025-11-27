<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Config;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/business",
     *     tags={"Business"},
     *     summary="Get list of business with optional filters",
     *     @OA\Parameter(name="email", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="mobile", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="code", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Business list retrieved successfully"),
     *     @OA\Response(response=404, description="No business available")
     * )
     */

    public function index(Request $request)
    {
        $query = Business::query();

        // Apply optional filters using when()
        $query->when($request->email, fn($q) => $q->where('email', $request->email))
            ->when($request->mobile, fn($q) => $q->where('mobile', $request->mobile))
            ->when($request->code, fn($q) => $q->where('code', $request->code))
            ->when($request->group_id, fn($q) => $q->where('group_id', $request->group_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->is_featured, fn($q) => $q->where('is_featured', $request->is_featured));

        // $apiUrl = env('API_URL');
        $apiUrl = 'https://api.findmenu.in/';

        $data = $query->with('group')->get()->map(function ($item) use ($apiUrl) {
            $item->logo_img_url = $item->logo
                ? $apiUrl  . $item->logo
                : 'https://via.placeholder.com/200x200?text=No+Image';
            $item->banner_img_url = $item->bannerImage
                ? $apiUrl  . $item->bannerImage
                : 'https://api.findmenu.in/images/no-image.png';
            $item->img_url = $item->image
                ? $apiUrl  . $item->image
                : 'https://api.findmenu.in/images/no-image.png';

            return $item;
        });

        if ($data->isEmpty()) {
            return $this->sendError('No data found', 'No business available', 404);
        }

        return $this->sendResponse($data, 'Business list retrieved successfully');
    }


    /**
     * @OA\Post(
     *     path="/business",
     *     tags={"Business"},
     *     summary="Create new business with config and admin user",
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Business")),
     *     @OA\Response(response=200, description="Business, config, and user created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:businesses,code',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'logo' => 'nullable|string',
            'image' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'bannerImage' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'type' => 'nullable|string',
            'status' => 'nullable|integer',
            'group_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'currency' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        // 1. Create Business
        $business = Business::create($request->all());

        $business->refresh();

        // 2. Create Config for the Business
        $config = Config::create([
            'businessId' => $business->id,
            'json' => json_encode(['example' => 'config']),
            'status' => 1
        ]);

        $config->refresh();

        // 3. Create Admin User for the Business
        $user = User::create([
            'name'     => $business->name,
            'email'    => $business->email,
            'password' => Hash::make($business->name . "123"), // set a secure default or generate
            'status'   => 1,
            'businessId' => $business->id,
        ]);

        // Refresh to get full record including defaults, timestamps, casts, etc.
        $user->refresh();

        return $this->sendResponse([
            'business' => $business,
            'config'   => $config,
            'user'     => $user,
        ], 'Business, config, and user created successfully');
    }

    /**
     * @OA\Get(
     *     path="/business/{id}",
     *     tags={"Business"},
     *     summary="Get business by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Business retrieved successfully"),
     *     @OA\Response(response=404, description="Business not found")
     * )
     */

    public function show($id)
    {
        // $data = Business::find($id);
        $data = Business::with('group')->find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Business not found', 404);
        }

        return $this->sendResponse($data, 'Business retrieved successfully');
    }

    /**
     * @OA\Put(
     *     path="/business/{id}",
     *     tags={"Business"},
     *     summary="Update business by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Business")),
     *     @OA\Response(response=200, description="Business updated successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Business not found")
     * )
     */

    public function update(Request $request, $id)
    {
        $data = Business::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Business not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100',
            'code' => 'sometimes|required|string|max:50|unique:businesses,code,' . $data->id,
            'email' => 'nullable|email',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'logo' => 'nullable|string|max:100',
            'image' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'bannerImage' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'nullable|integer',
            'group_id' => 'nullable|integer',
            'type' => 'nullable|string',
            'description' => 'nullable|string',
            'currency' => 'nullable|string|max:20',
        ]);

        $input = $request->except('image'); // Get all except image

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $folderName = $request->code ?? 'uploads';
            $destinationPath = public_path("images/$folderName");
            $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Optional: Delete old image
            if ($data->image && file_exists(public_path($data->image))) {
                unlink(public_path($data->image));
            }

            $file->move($destinationPath, $fileName);

            $input['image'] = "images/$folderName/$fileName";
        }

        if ($request->hasFile('bannerImage')) {
            $file = $request->file('bannerImage');
            $folderName = $request->code ?? 'uploads';
            $destinationPath = public_path("images/$folderName");
            $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Optional: Delete old image
            if ($data->image && file_exists(public_path($data->image))) {
                unlink(public_path($data->image));
            }

            $file->move($destinationPath, $fileName);

            $input['bannerImage'] = "images/$folderName/$fileName";
        }

        if (!$data->update($input)) {
            return $this->sendError('Update failed', 'Could not update business', 500);
        }

        return $this->sendResponse($data, 'Business updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/business/{id}",
     *     tags={"Business"},
     *     summary="Delete (soft) business by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Business deleted successfully"),
     *     @OA\Response(response=404, description="Business not found")
     * )
     */

    public function destroy($id)
    {
        $data = Business::find($id);

        if (!$data) {
            return $this->sendError('Not Found', 'Business not found', 404);
        }

        $data->delete();
        return $this->sendResponse(null, 'Business deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/business/{id}/restore",
     *     tags={"Business"},
     *     summary="Restore soft-deleted business",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Business restored successfully"),
     *     @OA\Response(response=404, description="Business not found in trash")
     * )
     */

    public function restore($id)
    {
        $data = Business::onlyTrashed()->find($id);

        if (!$data) {
            return $this->sendError('Business not found in trash', [], 404);
        }

        $data->restore();

        return $this->sendResponse($data, 'Business restored successfully');
    }

    /**
     * @OA\Get(
     *     path="/business/trashed",
     *     tags={"Business"},
     *     summary="Get all soft-deleted business",
     *     @OA\Response(response=200, description="Trashed business retrieved successfully"),
     *     @OA\Response(response=404, description="No trashed business found")
     * )
     */

    public function trashed()
    {
        $data = Business::onlyTrashed()->get();

        if ($data->isEmpty()) {
            return $this->sendError('No trashed business found', [], 404);
        }

        return $this->sendResponse($data, 'Trashed business retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/business/code/{code}",
     *     summary="Get business details by code including main categories, subcategories and items",
     *     tags={"Business"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         description="Business unique code",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Business data fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/components/schemas/Business"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Business not found"
     *     )
     * )
     */
    public function getBusinessDetails($code)
    {
        $data = Business::with([
            'category.subCategory.items',
            'config'
        ])
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();

        if (!$data) {
            return $this->sendError('Business not found', [], 404);
        }

        return $this->sendResponse($data, 'Business data fetched successfully');
    }

    public function getBusinessData($businessId)
    {
        $business = Business::with([
            'category' => function ($q) {
                $q->where('status', 1)->orderBy('menuOrderId');
            },
            'category.subCategory' => function ($q) {
                $q->where('status', 1)->orderBy('menuOrderId');
            },
            'category.subCategory.items' => function ($q) use ($businessId) {
                $q->where('status', 1)
                    ->where('businessId', $businessId)
                    ->orderBy('menuOrderId');
            },
            'config'
        ])
            ->where('businessId', $businessId)
            ->first();

        if (!$business) {
            return $this->sendError('Business not found', [], 404);
        }

        return $this->sendResponse($business, 'Business data fetched successfully');
    }

    public function checkBusinessDetailsByCode(Request $request, $code)
    {
        // Load business with all necessary relations in one go

        $data = Business::whereRaw('LOWER(code) = ?', [strtolower($code)])->first();

        if (!$data) {
            return $this->sendError('Business not found', [], 404);
        } else {
            if ($data->status) {
                return $this->sendResponse($data, 'Pleae contact admin to activate your business');
            }
        }
    }
    public function getBusinessDetailsByCode(Request $request, $code)
    {
        $business = Business::with([
            'category' => function ($q) {
                $q->where('status', 1)->orderBy('menuOrderId');
            },
            'category.subCategory' => function ($q) {
                $q->where('status', 1)->orderBy('menuOrderId');
            },
            'category.subCategory.items' => function ($q) {
                $q->where('status', 1)->orderBy('menuOrderId');
            },
            'config'
        ])
            ->where('code', $code);

        if ($request->has('status')) {
            $business->where('status', $request->input('status'));
        }

        $apiUrl = env('ee', 'https://api.findmenu.in/');

        $data = $business->first();

        if (!$data) {
            return $this->sendError('Business not found', [], 404);
        }

        // Add images for business
        $data->logo_img_url = $data->logo ? $apiUrl . $data->logo : '';
        $data->banner_img_url = $data->bannerImage ? $apiUrl . $data->bannerImage : 'https://api.findmenu.in/images/no-image.png';
        $data->img_url = $data->image ? $apiUrl . $data->image : 'https://api.findmenu.in/images/no-image.png';

        /* ------------------------------------------------------------------
        ADD image_url FOR:
        - Categories
        - Subcategories
        - Items
    -------------------------------------------------------------------*/
        foreach ($data->category as $cat) {

            // Category image
            $cat->image_url = $cat->image
                ? $apiUrl . $cat->image
                : 'https://api.findmenu.in/images/no-image.png';

            foreach ($cat->subCategory as $sub) {

                // Subcategory image
                $sub->image_url = $sub->image
                    ? $apiUrl . $sub->image
                    : 'https://api.findmenu.in/images/no-image.png';

                foreach ($sub->items as $item) {

                    // Item image
                    $item->image_url = $item->image
                        ? $apiUrl . $item->image
                        : 'https://api.findmenu.in/images/no-image.png';
                }
            }
        }

        return $this->sendResponse($data, 'Business details fetched successfully');
    }


    public function getBusinessDetailsByCode1(Request $request, $code)
    {
        // Load business with all necessary relations in one go
        $business = Business::with([
            'category' => function ($q) {
                $q->where('status', 1)->orderBy('menuOrderId');
            },
            'category.subCategory' => function ($q) {
                $q->where('status', 1)->orderBy('menuOrderId');
            },
            'category.subCategory.items' => function ($q) {
                $q->where('status', 1)->orderBy('menuOrderId');
            },
            'config'
        ])
            ->whereRaw('LOWER(code) = ?', [strtolower($code)]);

        // Optional: filter by status if requested
        if ($request->has('status')) {
            $business->where('status', $request->input('status'));
        }
        $apiUrl = env('ee', 'https://api.findmenu.in/');

        $data = $business->first();

        if ($data) {
            $data->logo_img_url = $data->logo
                ? $apiUrl . $data->logo
                : '';
            $data->banner_img_url = $data->bannerImage
                ? $apiUrl . $data->bannerImage
                : 'https://api.findmenu.in/images/no-image.png';
            $data->img_url = $data->image
                ? $apiUrl . $data->image
                : 'https://api.findmenu.in/images/no-image.png';
        }

        if (!$data) {
            return $this->sendError('Business not found', [], 404);
        }

        $subscription = Subscription::where('businessId', $data->id)
            // ->where('status', 'active') // Only active subscriptions
            ->where('ends_at', '>=', now()) // Not expired
            ->first();

        $hasActiveSubscription = !is_null($subscription);

        $data['subscription'] = $subscription;
        $data['isSubscribed'] = $hasActiveSubscription;


        return $this->sendResponse($data, 'Business details fetched successfully');
    }

    /**
     * @OA\Get(
     *     path="/business/types",
     *     tags={"Business"},
     *     summary="Get unique business types from businesses table",
     *     description="Fetches distinct business type values from businesses table.",
     *     @OA\Response(
     *         response=200,
     *         description="Unique business types retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string", example="restaurant")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No business types found"
     *     )
     * )
     */
    public function getUniqueTypes()
    {
        $types = Business::whereNotNull('type')->distinct()->pluck('type');


        if ($types->isEmpty()) {
            return $this->sendError('No business types found', [], 404);
        }

        return $this->sendResponse($types, 'Unique business types retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/business/leading",
     *     tags={"Business"},
     *     summary="Get leading/featured businesses with logo images",
     *     description="Fetches featured businesses with their logo images for display on homepage.",
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of businesses to return (default: 10)",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Leading businesses retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John's Cafe"),
     *                 @OA\Property(property="code", type="string", example="johns-cafe"),
     *                 @OA\Property(property="logo", type="string", example="images/johns-cafe/logo.png"),
     *                 @OA\Property(property="logo_url", type="string", example="https://api.findmenu.in/storage/images/johns-cafe/logo.png"),
     *                 @OA\Property(property="type", type="string", example="restaurant")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No leading businesses found"
     *     )
     * )
     */
    public function getLeadingBusinesses(Request $request)
    {
        $limit = $request->input('limit', 10);
        // $apiUrl = env('API_URL', 'https://api.findmenu.in');
        $apiUrl = env('ee', 'https://api.findmenu.in/');

        $businesses = Business::where('is_featured', 1)
            ->whereNotNull('image')
            ->select('id', 'name', 'code', 'image', 'logo', 'bannerImage', 'businessType')
            ->with('config')
            ->limit($limit)
            ->get()
            ->map(function ($item) use ($apiUrl) {
                $item->logo_url = $item->logo
                    ? $apiUrl  . $item->logo
                    : null;
                $item->banner_url = $item->bannerImage
                    ? $apiUrl  . $item->bannerImage
                    : null;
                $item->image_url = $item->image
                    ? $apiUrl  . $item->image
                    : null;
                return $item;
            });

        if ($businesses->isEmpty()) {
            return $this->sendError('No leading businesses found', [], 404);
        }

        return $this->sendResponse($businesses, 'Leading businesses retrieved successfully');
    }
}
