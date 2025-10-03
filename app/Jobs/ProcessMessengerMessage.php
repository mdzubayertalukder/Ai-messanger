<?php

namespace App\Jobs;

use App\Models\FacebookPage;
use App\Models\Message;
use App\Services\ChatGPTService;
use App\Services\FacebookService;
use App\Services\ImageProcessingService;
use App\Services\ProductSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMessengerMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $senderId;
    protected $pageId;
    protected $messageData;
    protected $pageAccessToken;
    protected $facebookPageId;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($senderId, $pageId, $messageData, $pageAccessToken, $facebookPageId, $userId)
    {
        $this->senderId = $senderId;
        $this->pageId = $pageId;
        $this->messageData = $messageData;
        $this->pageAccessToken = $pageAccessToken;
        $this->facebookPageId = $facebookPageId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing messenger message', [
                'sender_id' => $this->senderId,
                'page_id' => $this->pageId,
                'has_attachments' => isset($this->messageData['attachments'])
            ]);

            // Initialize services
            $facebookService = app(FacebookService::class);
            $chatgptService = app(ChatGPTService::class);
            $imageProcessingService = app(ImageProcessingService::class);
            $productSearchService = new ProductSearchService($imageProcessingService);

            // Set ChatGPT configuration
            $chatgptConfig = [
                'api_key' => config('services.openai.api_key'),
                'model' => config('services.openai.model'),
                'max_tokens' => config('services.openai.max_tokens'),
            ];
            $chatgptService->setConfig($chatgptConfig);

            $messageText = $this->messageData['text'] ?? '';
            $hasAttachments = isset($this->messageData['attachments']) && !empty($this->messageData['attachments']);

            // Store incoming message
            $incomingMessage = Message::create([
                'user_id' => $this->userId,
                'facebook_page_id' => $this->facebookPageId,
                'sender_id' => $this->senderId,
                'recipient_id' => $this->pageId,
                'message_text' => $messageText,
                'direction' => 'incoming',
                'has_attachments' => $hasAttachments,
                'raw_data' => $this->messageData,
            ]);

            // Send typing indicator
            $facebookService->sendTypingIndicator($this->senderId, $this->pageAccessToken);

            // Check if message has image attachments
            if ($hasAttachments) {
                $imageAttachments = $this->extractImageAttachments($this->messageData['attachments']);
                
                if (!empty($imageAttachments)) {
                    Log::info('Processing image attachments', [
                        'sender_id' => $this->senderId,
                        'image_count' => count($imageAttachments)
                    ]);

                    $this->handleImageMessage($imageAttachments, $messageText, $facebookService, $imageProcessingService, $productSearchService);
                    return;
                }
            }

            // Handle text-only message with ChatGPT
            $this->handleTextMessage($messageText, $facebookService, $chatgptService);

        } catch (\Exception $e) {
            Log::error('Error processing messenger message', [
                'sender_id' => $this->senderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Send error message to user
            try {
                $facebookService = new FacebookService();
                $facebookService->sendMessage(
                    $this->senderId,
                    "Sorry, I'm having trouble processing your message right now. Please try again later.",
                    $this->pageAccessToken
                );
            } catch (\Exception $sendError) {
                Log::error('Failed to send error message', [
                    'sender_id' => $this->senderId,
                    'error' => $sendError->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle image message with product search
     */
    private function handleImageMessage($imageAttachments, $messageText, $facebookService, $imageProcessingService, $productSearchService)
    {
        try {
            $allProducts = [];

            foreach ($imageAttachments as $imageUrl) {
                Log::info('Processing image for product search', [
                    'sender_id' => $this->senderId,
                    'image_url' => $imageUrl
                ]);

                // Process image and get AI analysis
                $analysisResult = $imageProcessingService->analyzeImage($imageUrl);
                
                if ($analysisResult['success']) {
                    $imageAnalysis = $analysisResult['analysis'];
                    
                    // Search for matching products
                    $matchingProducts = $productSearchService->searchProducts($imageAnalysis, $this->userId);
                    
                    if (!empty($matchingProducts)) {
                        $allProducts = array_merge($allProducts, $matchingProducts);
                        Log::info('Found matching products', [
                            'sender_id' => $this->senderId,
                            'product_count' => count($matchingProducts)
                        ]);
                    }
                } else {
                    Log::warning('Image analysis failed', [
                        'sender_id' => $this->senderId,
                        'error' => $analysisResult['error'] ?? 'Unknown error'
                    ]);
                }
            }

            // Remove duplicates and limit results
            $allProducts = array_unique($allProducts, SORT_REGULAR);
            $allProducts = array_slice($allProducts, 0, 10); // Limit to 10 products

            if (!empty($allProducts)) {
                // Send product recommendations
                if (count($allProducts) == 1) {
                    // Send single product card
                    $result = $facebookService->sendProductCard($this->senderId, $allProducts[0], $this->pageAccessToken);
                } else {
                    // Send product carousel
                    $result = $facebookService->sendProductCarousel($this->senderId, $allProducts, $this->pageAccessToken);
                }

                if ($result['success']) {
                    // Store outgoing message
                    Message::create([
                        'user_id' => $this->userId,
                        'facebook_page_id' => $this->facebookPageId,
                        'sender_id' => $this->pageId,
                        'recipient_id' => $this->senderId,
                        'message_text' => 'Product recommendations based on your image',
                        'direction' => 'outgoing',
                        'responded_by_ai' => true,
                        'ai_response' => 'Product recommendations',
                        'product_recommendations' => $allProducts,
                    ]);

                    // Update product inquiry counts
                    foreach ($allProducts as $product) {
                        if (isset($product['id'])) {
                            $productModel = \App\Models\Product::find($product['id']);
                            if ($productModel) {
                                $productModel->incrementInquiries();
                            }
                        }
                    }
                } else {
                    Log::error('Failed to send product recommendations', [
                        'sender_id' => $this->senderId,
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                    
                    // Fallback to text message
                    $facebookService->sendMessage(
                        $this->senderId,
                        "I found some products that might match your image, but I'm having trouble displaying them right now. Please try again later.",
                        $this->pageAccessToken
                    );
                }
            } else {
                // No products found
                $facebookService->sendMessage(
                    $this->senderId,
                    "I couldn't find any products matching your image. Could you try uploading a clearer image or describe what you're looking for?",
                    $this->pageAccessToken
                );

                // Store outgoing message
                Message::create([
                    'user_id' => $this->userId,
                    'facebook_page_id' => $this->facebookPageId,
                    'sender_id' => $this->pageId,
                    'recipient_id' => $this->senderId,
                    'message_text' => "No products found matching the image",
                    'direction' => 'outgoing',
                    'responded_by_ai' => true,
                    'ai_response' => 'No products found',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error handling image message', [
                'sender_id' => $this->senderId,
                'error' => $e->getMessage()
            ]);

            // Send fallback message
            $facebookService->sendMessage(
                $this->senderId,
                "I'm having trouble analyzing your image right now. Please try again later or describe what you're looking for.",
                $this->pageAccessToken
            );
        }
    }

    /**
     * Handle text-only message with product search and ChatGPT
     */
    private function handleTextMessage($messageText, $facebookService, $chatgptService)
    {
        try {
            // First, search for products based on the text message
            $imageProcessingService = new ImageProcessingService();
            $productSearchService = new ProductSearchService($imageProcessingService);
            $matchingProducts = $productSearchService->searchProductsByText($messageText, $this->userId);
            
            if ($matchingProducts && $matchingProducts->isNotEmpty()) {
                // Products found - send product information
                Log::info('Products found for text query', [
                    'sender_id' => $this->senderId,
                    'query' => $messageText,
                    'products_count' => $matchingProducts->count()
                ]);
                
                // Format and send product response
                $productResponse = $productSearchService->formatProductsForMessenger($matchingProducts);
                $result = $facebookService->sendMessage($this->senderId, $productResponse, $this->pageAccessToken);
                
                if ($result['success']) {
                    // Store outgoing message
                    Message::create([
                        'user_id' => $this->userId,
                        'facebook_page_id' => $this->facebookPageId,
                        'sender_id' => $this->pageId,
                        'recipient_id' => $this->senderId,
                        'message_text' => $productResponse,
                        'direction' => 'outgoing',
                        'responded_by_ai' => true,
                        'ai_response' => 'Product search results',
                    ]);
                }
            } else {
                // No products found - use ChatGPT with system prompt
                $systemPrompt = "You are a helpful customer service assistant for an online store. When customers ask about products, you should be helpful and suggest they can browse the available products or provide more specific details about what they're looking for. Be friendly and professional.";
                
                $aiResponseData = $chatgptService->sendMessage($messageText, $systemPrompt);
                
                if ($aiResponseData && isset($aiResponseData['success']) && $aiResponseData['success']) {
                    $aiResponse = $aiResponseData['message'];
                    
                    // Send response back to Facebook
                    $result = $facebookService->sendMessage($this->senderId, $aiResponse, $this->pageAccessToken);
                    
                    if ($result['success']) {
                        // Store outgoing message
                        Message::create([
                            'user_id' => $this->userId,
                            'facebook_page_id' => $this->facebookPageId,
                            'sender_id' => $this->pageId,
                            'recipient_id' => $this->senderId,
                            'message_text' => $aiResponse,
                            'direction' => 'outgoing',
                            'responded_by_ai' => true,
                            'ai_response' => $aiResponse,
                        ]);
                    } else {
                        Log::error('Failed to send ChatGPT response', [
                            'sender_id' => $this->senderId,
                            'error' => $result['error'] ?? 'Unknown error'
                        ]);
                    }
                } else {
                    Log::error('ChatGPT response failed', [
                        'sender_id' => $this->senderId,
                        'response' => $aiResponseData
                    ]);
                    
                    // Send fallback message
                    $facebookService->sendMessage(
                        $this->senderId,
                        "I'm having trouble understanding your message right now. Please try again later.",
                        $this->pageAccessToken
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling text message', [
                'sender_id' => $this->senderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract image attachments from message attachments
     */
    private function extractImageAttachments($attachments)
    {
        $imageUrls = [];
        
        foreach ($attachments as $attachment) {
            if (isset($attachment['type']) && $attachment['type'] === 'image') {
                if (isset($attachment['payload']['url'])) {
                    $imageUrls[] = $attachment['payload']['url'];
                }
            }
        }
        
        return $imageUrls;
    }
}