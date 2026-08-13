<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InquiryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteImageController;
use App\Http\Controllers\TransactionCategoryController;
use App\Http\Controllers\LegitimacyProofController;
use App\Http\Controllers\VouchController;

// Public auth routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('/site-images', [SiteImageController::class, 'index']);

// Public read-only routes — anyone browsing the site can hit these
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/slug/{slug}', [ProductController::class, 'showBySlug']);
Route::get('/products/sizes', [ProductController::class, 'availableSizes']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::post('/inquiries', [InquiryController::class, 'store']);

// Legitimacy page — public
Route::get('/transaction-categories', [TransactionCategoryController::class, 'index']);
Route::get('/legitimacy-proofs', [LegitimacyProofController::class, 'index']);
Route::get('/vouches', [VouchController::class, 'index']);
Route::post('/vouches', [VouchController::class, 'store'])->middleware('throttle:5,1');

// Protected routes — require a valid Sanctum token (admin only)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/{product}/images', [ProductController::class, 'storeImages']);
    Route::delete('/products/{product}/images/{image}', [ProductController::class, 'destroyImage']);
    Route::put('/products/{product}/images/reorder', [ProductController::class, 'reorderImages']);

    Route::get('/inquiries', [InquiryController::class, 'index']);
    Route::put('/inquiries/{inquiry}', [InquiryController::class, 'update']);

    Route::post('/site-images/{key}', [SiteImageController::class, 'upload']);
    Route::delete('/site-images/{key}', [SiteImageController::class, 'destroy']);

    Route::post('/categories/{category}/image', [CategoryController::class, 'uploadImage']);
    Route::delete('/categories/{category}/image', [CategoryController::class, 'destroyImage']);
    Route::post('/categories/{category}/hover-image', [CategoryController::class, 'uploadHoverImage']);
    Route::delete('/categories/{category}/hover-image', [CategoryController::class, 'destroyHoverImage']);

    // Legitimacy page — admin management
    Route::post('/transaction-categories', [TransactionCategoryController::class, 'store']);
    Route::put('/transaction-categories/{transactionCategory}', [TransactionCategoryController::class, 'update']);
    Route::delete('/transaction-categories/{transactionCategory}', [TransactionCategoryController::class, 'destroy']);

    Route::post('/legitimacy-proofs', [LegitimacyProofController::class, 'store']);
    Route::delete('/legitimacy-proofs/{legitimacyProof}', [LegitimacyProofController::class, 'destroy']);

    Route::get('/vouches/admin', [VouchController::class, 'adminIndex']);
    Route::put('/vouches/{vouch}/status', [VouchController::class, 'updateStatus']);
    Route::delete('/vouches/{vouch}', [VouchController::class, 'destroy']);
});