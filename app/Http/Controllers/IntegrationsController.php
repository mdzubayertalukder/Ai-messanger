<?php

namespace App\Http\Controllers;

use App\Models\WooStore;
use App\Models\FacebookPage;
use App\Jobs\SyncWooProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class IntegrationsController extends Controller
{
    /**
     * Display the integrations page
     */
    public function index(): View
    {
        $wooStores = WooStore::where('user_id', auth()->id())->get();
        $facebookPages = FacebookPage::where('user_id', auth()->id())->get();
        
        return view('integrations.index', compact('wooStores', 'facebookPages'));
    }

    /**
     * Store a new WooCommerce integration
     */
    public function storeWooCommerce(Request $request): RedirectResponse
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'store_url' => 'required|url',
            'consumer_key' => 'required|string',
            'consumer_secret' => 'required|string',
            'wp_api' => 'boolean',
            'version' => 'string|in:wc/v1,wc/v2,wc/v3',
        ]);

        $store = WooStore::create([
            'user_id' => Auth::id(),
            'store_name' => $request->store_name,
            'store_url' => rtrim($request->store_url, '/'),
            'consumer_key' => $request->consumer_key,
            'consumer_secret' => $request->consumer_secret,
            'wp_api' => $request->boolean('wp_api', true),
            'version' => $request->version ?? 'wc/v3',
        ]);

        // Automatically sync products for the new store
        SyncWooProducts::dispatch($store);

        return redirect()->route('integrations.index')
            ->with('success', 'WooCommerce store connected successfully! Products are being synced in the background.');
    }

    /**
     * Remove a WooCommerce integration
     */
    public function destroyWooCommerce(WooStore $wooStore): RedirectResponse
    {
        if ($wooStore->user_id !== Auth::id()) {
            abort(403);
        }

        $wooStore->delete();

        return redirect()->route('integrations.index')
            ->with('success', 'WooCommerce store disconnected successfully!');
    }

    /**
     * Store Facebook integration
     */
    public function storeFacebook(Request $request): RedirectResponse
    {
        $request->validate([
            'page_access_token' => 'required|string',
            'page_id' => 'required|string',
            'page_name' => 'required|string|max:255',
        ]);

        // Store Facebook page in database
        \App\Models\FacebookPage::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'page_id' => $request->page_id,
            ],
            [
                'page_name' => $request->page_name,
                'access_token' => $request->page_access_token,
                'subscribed' => true,
                'webhook_verify_token' => config('services.facebook.verify_token'),
            ]
        );

        return redirect()->route('integrations.index')
            ->with('success', 'Facebook page connected successfully!');
    }

    /**
     * Remove Facebook integration
     */
    public function destroyFacebook(Request $request): RedirectResponse
    {
        $request->validate([
            'page_id' => 'required|string',
        ]);

        \App\Models\FacebookPage::where('user_id', Auth::id())
            ->where('page_id', $request->page_id)
            ->delete();

        return redirect()->route('integrations.index')
            ->with('success', 'Facebook page disconnected successfully!');
    }

    /**
     * Display the ChatGPT integration page
     */
    public function chatgpt(): View
    {
        $chatgptConfig = session('chatgpt_integration', []);
        
        return view('integrations.chatgpt', compact('chatgptConfig'));
    }

    /**
     * Store ChatGPT integration configuration
     */
    public function storeChatGPT(Request $request): RedirectResponse
    {
        $request->validate([
            'api_key' => 'required|string',
            'model' => 'required|string|in:gpt-3.5-turbo,gpt-4,gpt-4-turbo-preview',
            'max_tokens' => 'required|integer|min:1|max:4000',
        ]);

        // Store in session for now (in production, you'd store in database)
        session([
            'chatgpt_integration' => [
                'api_key' => $request->api_key,
                'model' => $request->model,
                'max_tokens' => $request->max_tokens,
                'connected_at' => now(),
            ]
        ]);

        return redirect()->route('integrations.chatgpt')
            ->with('success', 'ChatGPT integration configured successfully!');
    }

    /**
     * Test ChatGPT connection
     */
    public function testChatGPT(Request $request)
    {
        try {
            $chatgptService = app(\App\Services\ChatGPTService::class);
            
            // Use session config if available, otherwise use .env config
            $sessionConfig = session('chatgpt_integration');
            if ($sessionConfig) {
                $result = $chatgptService->testConnection($sessionConfig);
            } else {
                $result = $chatgptService->testConnection();
            }

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'ChatGPT connection successful!',
                    'response' => $result['response']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'ChatGPT connection failed'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store ChatGPT custom prompt configuration
     */
    public function storeChatGPTPrompt(Request $request)
    {
        $request->validate([
            'system_prompt' => 'required|string|max:2000',
        ]);

        try {
            // Store the custom prompt in session
            session(['chatgpt_system_prompt' => $request->system_prompt]);

            return redirect()->route('integrations.chatgpt')
                ->with('success', 'Custom prompt updated successfully! This will now be used for all Facebook Messenger conversations.');
        } catch (\Exception $e) {
            return redirect()->route('integrations.chatgpt')
                ->withErrors(['system_prompt' => 'Failed to update prompt: ' . $e->getMessage()]);
        }
    }
}