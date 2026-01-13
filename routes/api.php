<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\{
    LoginController,
    AuthController,
    ConfigController,
    ContactController,
    FeedbackController,
    ItemController,
    MainCategoryController,
    MenuController,
    NotificationController,
    BusinessController,
    BusinessTypeController,
    BusinessTypeFieldController,
    SubCategoryController,
    UserController,
    DashboardController,
    AdminController,
    GroupsController,
    PaymentController,
    PaymentStatusController,
    SubscriptionController,
    SubscriptionPlanController,
    TransactionController
};

Route::middleware(['validate.ui'])->group(function () {

    Route::get('admin', [AdminController::class, 'index']);
    Route::get('admin/{user}', [AdminController::class, 'show']);

    Route::post('admin/register', [AdminAuthController::class, 'register']);
    Route::post('admin/login', [AdminAuthController::class, 'login']);

    Route::post('contacts', [ContactController::class, 'store']);
    Route::post('feedbacks', [FeedbackController::class, 'store']);
    Route::post('/refresh', [AuthController::class, 'refresh']);


    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return response()->json(['message' => 'Email verified successfully']);
    })->middleware(['signed'])->name('email.verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link sent']);
    })->middleware(['auth:api', 'throttle:6,1'])->name('email.verification.send');

    // ------------------------
    // Public GET Routes (No Auth)
    // ------------------------

    // Auth routes for register/login (public)
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/dashboard/counts', [DashboardController::class, 'getCounts']);

    // Public GET routes for resources
    Route::get('config', [ConfigController::class, 'index']);
    Route::get('config/{config}', [ConfigController::class, 'show']);

    Route::get('groups', [GroupsController::class, 'index']);
    Route::get('groups/{group}', [GroupsController::class, 'show']);
    Route::get('group/{code}', [GroupsController::class, 'code']);

    Route::get('contacts', [ContactController::class, 'index']);
    Route::get('contacts/{contact}', [ContactController::class, 'show']);

    Route::get('feedbacks', [FeedbackController::class, 'index']);
    Route::get('feedbacks/{feedback}', [FeedbackController::class, 'show']);

    Route::get('main-categories', [MainCategoryController::class, 'index']);
    Route::get('main-categories/{category}', [MainCategoryController::class, 'show']);

    Route::get('sub-categories', [SubCategoryController::class, 'index']);
    Route::get('sub-categories-with-category', [SubCategoryController::class, 'withCategory']);
    Route::get('sub-categories/{subCategory}', [SubCategoryController::class, 'show']);

    Route::get('items', [ItemController::class, 'index']);
    Route::get('items-with-category', [ItemController::class, 'withCategory']);
    Route::get('items/{item}', [ItemController::class, 'show']);

    // Complete menu structure endpoint
    Route::get('menu/complete', [MenuController::class, 'getCompleteMenu']);
    Route::get('business/{code}/menu/complete', [MenuController::class, 'getCompleteMenuByCode']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);


    Route::get('business', [BusinessController::class, 'index']);
    Route::get('business/leading', [BusinessController::class, 'getLeadingBusinesses']);
    Route::get('business/types', [BusinessController::class, 'getUniqueTypes']);
    Route::get('business/code/{business}', [BusinessController::class, 'getBusinessDetailsByCode']);
    Route::get('business/{business}', [BusinessController::class, 'show']);

    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);

    Route::get('business-types', [BusinessTypeController::class, 'index']);
    Route::get('business-types/{id}', [BusinessTypeController::class, 'show']);
    Route::get('business-types/{businessTypeId}/fields', [BusinessTypeFieldController::class, 'index']);

    // Subscription Plans (Public)
    Route::get('subscription-plans', [SubscriptionPlanController::class, 'index']);
    Route::get('subscription-plans/{id}', [SubscriptionPlanController::class, 'show']);
    Route::get('plans-renew', [SubscriptionPlanController::class, 'getRenewalPlans']);

    // Subscriptions (Public GET)
    Route::get('subscriptions', [SubscriptionController::class, 'index']);
    Route::get('subscriptions/current', [SubscriptionController::class, 'getCurrent']);
    Route::get('subscriptions/{id}', [SubscriptionController::class, 'show']);

    // Payments (Public GET)
    Route::get('payments/history/{businessId}', [PaymentController::class, 'getHistory']);

    // Transactions (Public GET)
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::get('transactions/{id}', [TransactionController::class, 'show']);
    Route::get('transactions/summary/{businessId}', [TransactionController::class, 'getSummary']);

    // Payment callbacks (Public POST - no auth required for webhooks)
    Route::post('payments/callback', [PaymentController::class, 'callback']);

    // ------------------------
    // Private Routes (POST, PUT, DELETE) Require Auth
    // ------------------------
    Route::middleware(['auth:api', 'verified'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'me']);
        Route::post('/business/{id}/password', [AuthController::class, 'changePassword']);
        Route::post('/business/password/{id}', [AuthController::class, 'changeUserPassword']);
        
        // Theme management
        Route::post('/user/theme', [AuthController::class, 'updateTheme']);
        Route::get('/user/theme', [AuthController::class, 'getTheme']);

        // Group resource except GET
        Route::post('groups', [GroupsController::class, 'store']);
        Route::put('groups/{group}', [GroupsController::class, 'update']);
        Route::patch('groups/{group}', [GroupsController::class, 'update']);
        Route::delete('groups/{group}', [GroupsController::class, 'destroy']);


        // Config resource except GET
        Route::post('config', [ConfigController::class, 'store']);
        Route::put('config/{config}', [ConfigController::class, 'update']);
        Route::patch('config/{config}', [ConfigController::class, 'update']);
        Route::delete('config/{config}', [ConfigController::class, 'destroy']);




        // Repeat for other resources...
        Route::put('contacts/{contact}', [ContactController::class, 'update']);
        Route::patch('contacts/{contact}', [ContactController::class, 'update']);
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy']);

        // Specific routes must come before dynamic routes
        Route::put('feedbacks/mark-all-read', [FeedbackController::class, 'markAllAsRead']);
        Route::put('feedbacks/{id}/mark-read', [FeedbackController::class, 'markAsRead']);
        Route::put('feedbacks/{feedback}', [FeedbackController::class, 'update']);
        Route::patch('feedbacks/{feedback}', [FeedbackController::class, 'update']);
        Route::delete('feedbacks/{feedback}', [FeedbackController::class, 'destroy']);

        Route::post('items', [ItemController::class, 'store']);
        Route::put('items/{item}', [ItemController::class, 'update']);
        Route::patch('items/{item}', [ItemController::class, 'update']);
        Route::delete('items/{item}', [ItemController::class, 'destroy']);
        Route::post('items/menu-order', [ItemController::class, 'updateMenuOrder']);

        Route::post('main-categories', [MainCategoryController::class, 'store']);
        Route::put('main-categories/{category}', [MainCategoryController::class, 'update']);
        Route::patch('main-categories/{category}', [MainCategoryController::class, 'update']);
        Route::delete('main-categories/{category}', [MainCategoryController::class, 'destroy']);
        Route::post('main-categories/menu-order', [MainCategoryController::class, 'updateMenuOrder']);

        Route::post('sub-categories', [SubCategoryController::class, 'store']);
        Route::put('sub-categories/{subCategory}', [SubCategoryController::class, 'update']);
        Route::patch('sub-categories/{subCategory}', [SubCategoryController::class, 'update']);
        Route::delete('sub-categories/{subCategory}', [SubCategoryController::class, 'destroy']);
        Route::post('sub-categories/menu-order', [SubCategoryController::class, 'updateMenuOrder']);

        Route::post('notifications', [NotificationController::class, 'store']);
        // Specific routes must come before dynamic routes
        Route::put('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::put('notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);
        Route::put('notifications/{notification}', [NotificationController::class, 'update']);
        Route::patch('notifications/{notification}', [NotificationController::class, 'update']);
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

        Route::post('business', [BusinessController::class, 'store']);
        Route::put('business/{business}', [BusinessController::class, 'update']);
        Route::patch('business/{business}', [BusinessController::class, 'update']);
        Route::delete('business/{business}', [BusinessController::class, 'destroy']);

        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::patch('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);

        Route::post('admins', [AdminController::class, 'store']);
        Route::put('admins/{user}', [AdminController::class, 'update']);
        Route::patch('admins/{user}', [AdminController::class, 'update']);
        Route::delete('admins/{user}', [AdminController::class, 'destroy']);

        // Business Types routes
        Route::post('business-types', [BusinessTypeController::class, 'store']);
        Route::put('business-types/{id}', [BusinessTypeController::class, 'update']);
        Route::patch('business-types/{id}', [BusinessTypeController::class, 'update']);
        Route::delete('business-types/{id}', [BusinessTypeController::class, 'destroy']);

        // Business Type Fields routes
        Route::post('business-types/{businessTypeId}/fields', [BusinessTypeFieldController::class, 'store']);
        Route::put('business-type-fields/{id}', [BusinessTypeFieldController::class, 'update']);
        Route::patch('business-type-fields/{id}', [BusinessTypeFieldController::class, 'update']);
        Route::delete('business-type-fields/{id}', [BusinessTypeFieldController::class, 'destroy']);

        // Subscription Plans (Authenticated CRUD)
        Route::post('subscription-plans', [SubscriptionPlanController::class, 'store']);
        Route::put('subscription-plans/{id}', [SubscriptionPlanController::class, 'update']);
        Route::patch('subscription-plans/{id}', [SubscriptionPlanController::class, 'update']);
        Route::delete('subscription-plans/{id}', [SubscriptionPlanController::class, 'destroy']);

        // Subscriptions (Authenticated CRUD)
        Route::post('subscriptions', [SubscriptionController::class, 'store']);
        Route::put('subscriptions/{id}', [SubscriptionController::class, 'update']);
        Route::patch('subscriptions/{id}', [SubscriptionController::class, 'update']);
        Route::delete('subscriptions/{id}', [SubscriptionController::class, 'destroy']);
        Route::post('subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('subscriptions/{id}/renew', [SubscriptionController::class, 'renew']);
        Route::post('subscriptions/{id}/convert-trial', [SubscriptionController::class, 'convertTrial']);

        // Payments (Authenticated)
        Route::post('payments/initiate', [PaymentController::class, 'initiate']);
        Route::post('payments/phonepe/initiate', [PaymentController::class, 'initiatePhonePe']);
        Route::post('payments/razorpay/initiate', [PaymentController::class, 'initiateRazorpay']);
        Route::post('payments/stripe/initiate', [PaymentController::class, 'initiateStripe']);
    });
});

Route::middleware('web')->group(function () {
    Route::get('/api/login', [LoginController::class, 'showLoginForm'])->name('api.login');
    Route::post('/api/login', [LoginController::class, 'login']);
    Route::post('/api/logout', [LoginController::class, 'logout'])->name('api.logout');
});


Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
})->name('health');

Route::get('/seed-featured', function () {
    $businesses = \App\Models\Business::limit(6)->get();

    $logos = [
        'mcdonalds.png',
        'starbucks.png',
        'subway.png',
        'kfc.png',
        'pizzahut.png',
        'dominos.png',
    ];

    foreach ($businesses as $index => $business) {
        $business->update([
            'status' => 1,
            'is_featured' => 1,
            'logo' => $logos[$index] ?? 'logo.png',
        ]);
    }

    return response()->json([
        'message' => 'Updated ' . $businesses->count() . ' businesses to featured',
        'businesses' => $businesses
    ]);
});

Route::fallback(function () {
    return response()->json(['message' => 'Not Found'], 404);
});
