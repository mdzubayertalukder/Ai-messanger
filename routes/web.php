<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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
