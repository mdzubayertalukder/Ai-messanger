<?php

namespace App\Http\Controllers;

use App\Models\WebhookResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Facebook webhook verification (GET request)
     */
    public function verifyFacebook(Request $request)
    {
        $verifyToken = config('services.facebook.verify_token', 'your_verify_token_here');
        $challenge = $request->query('hub_challenge');
        $token = $request->query('hub_verify_token');
        $mode = $request->query('hub_mode');

        // Log the verification attempt
        Log::info('Facebook webhook verification attempt', [
            'mode' => $mode,
            'token' => $token,
            'challenge' => $challenge,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Store webhook response for display
        $webhookResponse = WebhookResponse::create([
            'user_id' => 1, // You might want to determine this differently
            'platform' => 'facebook',
            'event_type' => 'verification',
            'verify_token' => $token,
            'challenge' => $challenge,
            'request_data' => [
                'hub_mode' => $mode,
                'hub_verify_token' => $token,
                'hub_challenge' => $challenge,
                'query_params' => $request->query(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'received',
        ]);

        // Verify the token
        if ($mode === 'subscribe' && $token === $verifyToken) {
            // Update status to verified
            $webhookResponse->update([
                'status' => 'verified',
                'verified_at' => now(),
                'response_data' => [
                    'challenge_returned' => $challenge,
                    'verification_status' => 'success'
                ]
            ]);

            Log::info('Facebook webhook verification successful', ['challenge' => $challenge]);
            
            return response($challenge, 200)
                ->header('Content-Type', 'text/plain');
        } else {
            // Update status to failed
            $webhookResponse->update([
                'status' => 'failed',
                'response_data' => [
                    'error' => 'Invalid verify token or mode',
                    'expected_token' => $verifyToken,
                    'received_token' => $token,
                    'mode' => $mode
                ]
            ]);

            Log::warning('Facebook webhook verification failed', [
                'expected_token' => $verifyToken,
                'received_token' => $token,
                'mode' => $mode
            ]);

            return response('Forbidden', 403);
        }
    }

    /**
     * Handle Facebook webhook events (POST request)
     */
    public function handleFacebook(Request $request)
    {
        try {
            $data = $request->all();
            
            Log::info('Facebook webhook event received', $data);

            // Store webhook response
            $webhookResponse = WebhookResponse::create([
                'user_id' => 1, // You might want to determine this differently
                'platform' => 'facebook',
                'event_type' => 'webhook_event',
                'request_data' => $data,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'received',
            ]);

            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    if (isset($entry['messaging'])) {
                        foreach ($entry['messaging'] as $messaging) {
                            if (isset($messaging['message'])) {
                                $senderId = $messaging['sender']['id'];
                                $pageId = $messaging['recipient']['id'];
                                $messageText = $messaging['message']['text'] ?? '';
                                
                                // Get Facebook page from database
                                $facebookPage = \App\Models\FacebookPage::where('page_id', $pageId)
                                    ->where('subscribed', true)
                                    ->first();
                                
                                if (!$facebookPage) {
                                    \Log::error('Facebook page not found in database for page_id: ' . $pageId);
                                    return response('OK', 200);
                                }
                                
                                $pageAccessToken = $facebookPage->access_token;
                                
                                // Get ChatGPT configuration from environment
                                $chatgptConfig = [
                                    'api_key' => config('services.openai.api_key'),
                                    'model' => config('services.openai.model'),
                                    'max_tokens' => config('services.openai.max_tokens'),
                                ];
                                
                                if (!$chatgptConfig['api_key']) {
                                    \Log::error('ChatGPT API key not configured');
                                    return response('OK', 200);
                                }
                                
                                // Initialize services
                                $facebookService = new \App\Services\FacebookService();
                                $chatgptService = new \App\Services\ChatGPTService();
                                
                                // Set ChatGPT configuration
                                $chatgptService->setConfig($chatgptConfig);
                                
                                // Send typing indicator
                                $facebookService->sendTypingIndicator($senderId, $pageAccessToken);
                                
                                // Store incoming message
                                \App\Models\Message::create([
                                    'user_id' => $facebookPage->user_id,
                                    'facebook_page_id' => $facebookPage->id,
                                    'sender_id' => $senderId,
                                    'recipient_id' => $pageId,
                                    'message_text' => $messageText,
                                    'direction' => 'incoming',
                                ]);
                                
                                // Get AI response
                                $aiResponseData = $chatgptService->sendMessage($messageText);
                                
                                if ($aiResponseData && isset($aiResponseData['success']) && $aiResponseData['success']) {
                                    $aiResponse = $aiResponseData['message'];
                                    
                                    // Send response back to Facebook
                                    $facebookService->sendMessage($senderId, $aiResponse, $pageAccessToken);
                                    
                                    // Store outgoing message
                                    \App\Models\Message::create([
                                        'user_id' => $facebookPage->user_id,
                                        'facebook_page_id' => $facebookPage->id,
                                        'sender_id' => $pageId,
                                        'recipient_id' => $senderId,
                                        'message_text' => $aiResponse,
                                        'direction' => 'outgoing',
                                        'responded_by_ai' => true,
                                        'ai_response' => $aiResponse,
                                    ]);
                                } else {
                                    \Log::error('ChatGPT response failed', ['response' => $aiResponseData]);
                                }
                            }
                        }
                    }
                }
            }

            $webhookResponse->update([
                'status' => 'processed',
                'response_data' => [
                    'processed_at' => now(),
                    'events_count' => count($data['entry'] ?? [])
                ]
            ]);

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('Facebook webhook error: ' . $e->getMessage());
            return response('Error', 500);
        }
    }

    /**
     * Store message in database
     */
    private function storeMessage($senderId, $messageText, $direction, $externalMessageId = null, $originalMessage = null)
    {
        try {
            $messageData = [
                'user_id' => 1, // You might want to determine this differently
                'sender_id' => $direction === 'inbound' ? $senderId : 'page',
                'recipient_id' => $direction === 'inbound' ? 'page' : $senderId,
                'direction' => $direction,
                'message_text' => $messageText,
                'external_message_id' => $externalMessageId,
                'responded_by_ai' => $direction === 'outbound',
            ];

            if ($direction === 'outbound' && $originalMessage) {
                $messageData['ai_response'] = $messageText;
                $messageData['ai_confidence'] = 0.95; // You can implement confidence scoring
            }

            \App\Models\Message::create($messageData);
        } catch (\Exception $e) {
            Log::error('Failed to store message', [
                'sender_id' => $senderId,
                'direction' => $direction,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display webhook responses for authenticated user
     */
    public function index(Request $request)
    {
        $platform = $request->get('platform', 'facebook');
        $eventType = $request->get('event_type');
        
        $query = WebhookResponse::query()
            ->where('platform', $platform)
            ->orderBy('created_at', 'desc');

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        $webhookResponses = $query->paginate(20);

        return view('webhooks.index', compact('webhookResponses', 'platform', 'eventType'));
    }

    /**
     * Show detailed webhook response
     */
    public function show(WebhookResponse $webhookResponse)
    {
        return view('webhooks.show', compact('webhookResponse'));
    }

    /**
     * Clear all webhook responses
     */
    public function clear(Request $request)
    {
        $platform = $request->get('platform', 'facebook');
        
        WebhookResponse::where('platform', $platform)->delete();
        
        return redirect()->route('webhooks.index', ['platform' => $platform])
            ->with('success', 'All webhook responses cleared successfully!');
    }

    /**
     * Test Facebook verification (simulates what Facebook would send)
     */
    public function testFacebookVerification(Request $request)
    {
        // Simulate Facebook's verification request with different scenarios
        $scenarios = [
            'valid' => [
                'hub_mode' => 'subscribe',
                'hub_verify_token' => config('services.facebook.verify_token'),
                'hub_challenge' => 'test_challenge_' . time()
            ],
            'invalid_token' => [
                'hub_mode' => 'subscribe', 
                'hub_verify_token' => 'wrong_token',
                'hub_challenge' => 'test_challenge_' . time()
            ],
            'missing_params' => [
                'hub_mode' => 'subscribe'
            ]
        ];

        $scenario = $request->get('scenario', 'valid');
        $testParams = $scenarios[$scenario] ?? $scenarios['valid'];

        // Make internal request to the verification endpoint
        $response = $this->verifyFacebook(new Request($testParams));

        return response()->json([
            'scenario' => $scenario,
            'test_params' => $testParams,
            'response_status' => $response->getStatusCode(),
            'response_content' => $response->getContent(),
            'message' => 'This simulates what Facebook would receive when calling your webhook verification endpoint.'
        ]);
    }
}
