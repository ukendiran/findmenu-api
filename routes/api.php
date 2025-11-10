<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    LoginController,
    AuthController,
    ConfigController,
    ContactController,
    FeedbackController,
    ItemController,
    MainCategoryController,
    NotificationController,
    BusinessController,
    SubCategoryController,
    UserController,
    DashboardController,
    AdminController,
    GroupsController
};

use Illuminate\Foundation\Auth\EmailVerificationRequest;

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
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link sent']);
    })->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');

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

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);

    Route::get('business', [BusinessController::class, 'index']);
    Route::get('business/leading', [BusinessController::class, 'getLeadingBusinesses']);
    Route::get('business/types', [BusinessController::class, 'getUniqueTypes']);
    Route::get('business/code/{business}', [BusinessController::class, 'getBusinessDetailsByCode']);
    Route::get('business/{business}', [BusinessController::class, 'show']);

    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);

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
        Route::post('contacts/{id}/restore', [ContactController::class, 'restore']);
        Route::get('contacts/trashed', [ContactController::class, 'trashed']);

        Route::put('feedbacks/{feedback}', [FeedbackController::class, 'update']);
        Route::patch('feedbacks/{feedback}', [FeedbackController::class, 'update']);
        Route::delete('feedbacks/{feedback}', [FeedbackController::class, 'destroy']);
        Route::post('feedbacks/{id}/restore', [FeedbackController::class, 'restore']);
        Route::get('feedbacks/trashed', [FeedbackController::class, 'trashed']);

        Route::post('items', [ItemController::class, 'store']);
        Route::put('items/{item}', [ItemController::class, 'update']);
        Route::patch('items/{item}', [ItemController::class, 'update']);
        Route::delete('items/{item}', [ItemController::class, 'destroy']);
        Route::post('items/{id}/restore', [ItemController::class, 'restore']);
        Route::get('items/trashed', [ItemController::class, 'trashed']);
        Route::post('items/menu-order', [ItemController::class, 'updateMenuOrder']);

        Route::post('main-categories', [MainCategoryController::class, 'store']);
        Route::put('main-categories/{category}', [MainCategoryController::class, 'update']);
        Route::patch('main-categories/{category}', [MainCategoryController::class, 'update']);
        Route::delete('main-categories/{category}', [MainCategoryController::class, 'destroy']);
        Route::post('main-categories/{id}/restore', [MainCategoryController::class, 'restore']);
        Route::get('main-categories/trashed', [MainCategoryController::class, 'trashed']);
        Route::post('main-categories/menu-order', [MainCategoryController::class, 'updateMenuOrder']);

        Route::post('sub-categories', [SubCategoryController::class, 'store']);
        Route::put('sub-categories/{subCategory}', [SubCategoryController::class, 'update']);
        Route::patch('sub-categories/{subCategory}', [SubCategoryController::class, 'update']);
        Route::delete('sub-categories/{subCategory}', [SubCategoryController::class, 'destroy']);
        Route::post('sub-categories/{id}/restore', [SubCategoryController::class, 'restore']);
        Route::get('sub-categories/trashed', [SubCategoryController::class, 'trashed']);
        Route::post('sub-categories/menu-order', [SubCategoryController::class, 'updateMenuOrder']);

        Route::post('notifications', [NotificationController::class, 'store']);
        Route::put('notifications/{notification}', [NotificationController::class, 'update']);
        Route::patch('notifications/{notification}', [NotificationController::class, 'update']);
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
        Route::post('notifications/{id}/restore', [NotificationController::class, 'restore']);
        Route::get('notifications/trashed', [NotificationController::class, 'trashed']);

        Route::post('business', [BusinessController::class, 'store']);
        Route::put('business/{business}', [BusinessController::class, 'update']);
        Route::patch('business/{business}', [BusinessController::class, 'update']);
        Route::delete('business/{business}', [BusinessController::class, 'destroy']);
        Route::post('business/{id}/restore', [BusinessController::class, 'restore']);
        Route::get('business/trashed', [BusinessController::class, 'trashed']);

        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::patch('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
        Route::post('users/{id}/restore', [UserController::class, 'restore']);
        Route::get('users/trashed', [UserController::class, 'trashed']);

        Route::post('admins', [AdminController::class, 'store']);
        Route::put('admins/{user}', [AdminController::class, 'update']);
        Route::patch('admins/{user}', [AdminController::class, 'update']);
        Route::delete('admins/{user}', [AdminController::class, 'destroy']);
        Route::post('admins/{id}/restore', [AdminController::class, 'restore']);
        Route::get('admins/trashed', [AdminController::class, 'trashed']);
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
