<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Product API Routes
Route::prefix('products')->group(function () {
    // Get all products for a specific user
    Route::get('/user/{userId}', [ProductApiController::class, 'getUserProducts']);
    
    // Get specific product details
    Route::get('/{productId}', [ProductApiController::class, 'getProduct']);
    
    // Get user's product analytics
    Route::get('/user/{userId}/analytics', [ProductApiController::class, 'getUserProductAnalytics']);
    
    // Update product inquiry count
    Route::post('/{productId}/inquiry', [ProductApiController::class, 'incrementInquiry']);
    
    // Update product sale count
    Route::post('/{productId}/sale', [ProductApiController::class, 'incrementSale']);
    
    // Get products by store
    Route::get('/store/{storeId}', [ProductApiController::class, 'getStoreProducts']);
    
    // Search products (admin only)
    Route::get('/search/all', [ProductApiController::class, 'searchProducts'])->middleware('auth:sanctum');
});

// Additional API routes can be added here for other features
// Route::prefix('orders')->group(function () {
//     // Order API endpoints
// });

// Route::prefix('customers')->group(function () {
//     // Customer API endpoints  
// });