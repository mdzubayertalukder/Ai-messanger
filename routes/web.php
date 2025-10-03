<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\SalesDashboardController;
use App\Http\Controllers\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Dashboard routes
Route::middleware(['auth', 'verified'])->group(function () {
    // User Dashboard
    Route::get('/user-dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
    Route::get('/user-dashboard/products', [UserDashboardController::class, 'products'])->name('user.products');
    Route::get('/user-dashboard/orders', [UserDashboardController::class, 'orders'])->name('user.orders');
    Route::get('/user-dashboard/analytics', [UserDashboardController::class, 'analytics'])->name('user.analytics');
    
    // Sales Dashboard
    Route::get('/sales-dashboard', [SalesDashboardController::class, 'index'])->name('sales.dashboard');
    Route::get('/sales-dashboard/orders', [SalesDashboardController::class, 'orders'])->name('sales.orders');
    Route::get('/sales-dashboard/customers', [SalesDashboardController::class, 'customers'])->name('sales.customers');
    Route::get('/sales-dashboard/analytics', [SalesDashboardController::class, 'analytics'])->name('sales.analytics');
    
    // Admin Dashboard (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/admin-dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin-dashboard/users', [AdminDashboardController::class, 'users'])->name('admin.users');
        Route::get('/admin-dashboard/users/{user}', [AdminDashboardController::class, 'userDetails'])->name('admin.user.details');
        Route::get('/admin-dashboard/stores', [AdminDashboardController::class, 'stores'])->name('admin.stores');
        Route::get('/admin-dashboard/analytics', [AdminDashboardController::class, 'analytics'])->name('admin.analytics');
    });
});

// Webhook routes (public - no auth required for Facebook to access)
Route::middleware('log.webhook')->group(function () {
    Route::get('/webhook/facebook', [WebhookController::class, 'verifyFacebook'])->name('webhook.facebook.verify');
    Route::post('/webhook/facebook', [WebhookController::class, 'handleFacebook'])->name('webhook.facebook.handle');
});

// Test endpoint to simulate Facebook verification (for debugging)
Route::get('/test-facebook-webhook', [WebhookController::class, 'testFacebookVerification'])->name('webhook.facebook.test');

// Debug route to check session data
Route::get('/debug-session', function () {
    return response()->json([
        'facebook_integration' => session('facebook_integration'),
        'all_session' => session()->all()
    ]);
})->name('debug.session');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Integrations routes
    Route::get('/integrations', [IntegrationsController::class, 'index'])->name('integrations.index');
    Route::post('/integrations/facebook', [IntegrationsController::class, 'storeFacebook'])->name('integrations.facebook.store');
    Route::delete('/integrations/facebook', [IntegrationsController::class, 'destroyFacebook'])->name('integrations.facebook.destroy');
    Route::post('/integrations/woocommerce', [IntegrationsController::class, 'storeWooCommerce'])->name('integrations.woocommerce.store');
    Route::delete('/integrations/woocommerce/{wooStore}', [IntegrationsController::class, 'destroyWooCommerce'])->name('integrations.woocommerce.destroy');
    
    // ChatGPT Integration routes
    Route::get('/integrations/chatgpt', [IntegrationsController::class, 'chatgpt'])->name('integrations.chatgpt');
    Route::post('/integrations/chatgpt', [IntegrationsController::class, 'storeChatGPT'])->name('integrations.chatgpt.store');
    Route::post('/integrations/chatgpt/test', [IntegrationsController::class, 'testChatGPT'])->name('integrations.chatgpt.test');
    Route::post('/integrations/chatgpt/prompt', [IntegrationsController::class, 'storeChatGPTPrompt'])->name('integrations.chatgpt.prompt');
    
    // Webhook management routes (authenticated)
    Route::get('/webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
    Route::get('/webhooks/{webhookResponse}', [WebhookController::class, 'show'])->name('webhooks.show');
    Route::delete('/webhooks/clear', [WebhookController::class, 'clear'])->name('webhooks.clear');
});

require __DIR__ . '/auth.php';
