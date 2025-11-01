<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Business;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Auth\Events\Registered;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/login",
     *     tags={"Auth"},
     *     summary="User login",
     *     description="Logs in a user and returns a token.",
     *     operationId="login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return $this->sendError('Invalid credentials', 'Unauthorized', 401);
        }

        $user = User::find(auth('api')->id());
        $config = Config::where('businessId', $user->businessId)->first();

        return $this->sendResponse([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user,
            'config' => $config,
            'business' => Business::with('group')->find($user->businessId),
        ], 'Login successful');
    }

    /**
     * @OA\Post(
     *     path="/register",
     *     tags={"Auth"},
     *     summary="Register a new user with business and config",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation", "code"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="code", type="string", example="johns-biz"),
     *             @OA\Property(property="password", type="string", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="business", type="object"),
     *                 @OA\Property(property="config", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'code'    => 'required|string|unique:businesses',
            'password' => 'required|string|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        // Create Business
        $business = Business::create([
            'name'  => $request->name,
            'email' => $request->email,
            'code'  => $request->code,
            'status' => 1,
        ]);

        // Create Config
        $config = Config::create([
            'businessId' => $business->id,
            'json' => json_encode(['init' => true]),
            'status' => 1
        ]);

        // Create User
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'status'   => 1,
            'businessId' => $business->id,
        ]);

        // 🔔 Trigger email verification
        event(new Registered($user));

        return $this->sendResponse([
            'user'     => $user,
            'business' => $business,
            'config'   => $config,
        ], 'User registered successfully. Please check your email to verify your account.');
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     tags={"Auth"},
     *     summary="Logout the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully logged out"),
     *             @OA\Property(property="data", type="string", nullable=true)
     *         )
     *     )
     * )
     */

    public function logout()
    {
        auth('api')->logout();
        return $this->sendResponse(null, 'Successfully logged out');
    }

    /**
     * @OA\Get(
     *     path="/me",
     *     tags={"Auth"},
     *     summary="Get profile of the logged-in user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */

    public function me()
    {
        return $this->sendResponse(auth('api')->user(), 'User profile');
    }

    /**
     * @OA\Post(
     *   path="/refresh",
     *   summary="Refresh JWT token",
     *   tags={"Auth"},
     *   @OA\Response(response=200, description="Token refreshed")
     * )
     */
    public function refresh()
    {
        try {
            // Use the JWTAuth façade so Intelephense sees the method:
            $newToken = JWTAuth::refresh();

            // Get the TTL (in minutes) from the same façade:
            $expiresIn = JWTAuth::factory()->getTTL() * 60;

            return $this->sendResponse([
                'token'      => $newToken,
                'expires_in' => $expiresIn,
            ], 'Token refreshed');
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token invalid', [], 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/business/{id}/password",
     *     tags={"Auth"},
     *     summary="Change business user password (admin only, no old password needed)",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"new_password", "confirm_password"},
     *             @OA\Property(property="new_password", type="string", example="newpassword123"),
     *             @OA\Property(property="confirm_password", type="string", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function changePassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'new_password'     => 'required|string|min:6',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        $user = User::where('businessId', $id)->first();

        if (! $user) {
            return $this->sendError('User not found for given business ID', [], 404);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->sendResponse(null, 'Password updated successfully');
    }

    /**
     * @OA\Post(
     *     path="/business/{id}/password",
     *     tags={"Auth"},
     *     summary="Change business user password (admin bypasses old password, others require it)",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"new_password", "confirm_password"},
     *             @OA\Property(property="old_password", type="string", example="currentpass"),
     *             @OA\Property(property="new_password", type="string", example="newpassword123"),
     *             @OA\Property(property="confirm_password", type="string", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function changeUserPassword(Request $request, $id)
    {
        $user = User::where('businessId', $id)->first();

        if (! $user) {
            return $this->sendError('User not found for given business ID', [], 404);
        }

        $rules = [
            'old_password'     => 'required|string',
            'new_password'     => 'required|string|min:6',
            'confirm_password' => 'required|same:new_password',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors(), 422);
        }

        // If not admin, verify old password
        if (! Hash::check($request->old_password, $user->password)) {
            return $this->sendError('Old password does not match', ['Old password does not match'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->sendResponse(null, 'Password updated successfully');
    }
}
