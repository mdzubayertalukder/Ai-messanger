<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService
{
    private $pageAccessToken;
    private $baseUrl = 'https://graph.facebook.com/v23.0';

    public function __construct()
    {
        // Get the page access token from session (you might want to get this from database)
        $facebookIntegration = session('facebook_integration');
        $this->pageAccessToken = $facebookIntegration['page_access_token'] ?? null;
    }

    /**
     * Send a text message to a Facebook user
     */
    public function sendMessage($recipientId, $message, $accessToken = null)
    {
        try {
            $token = $accessToken ?: $this->pageAccessToken;
            if (empty($token)) {
                throw new \Exception('Facebook page access token is not configured');
            }

            // Use the exact format from Facebook Messenger API documentation
            $payload = [
                'recipient' => [
                    'id' => $recipientId
                ],
                'messaging_type' => 'RESPONSE',
                'message' => [
                    'text' => $message
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/me/messages?access_token=' . $token, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Facebook message sent successfully', [
                    'recipient_id' => $recipientId,
                    'message_id' => $data['message_id'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'message_id' => $data['message_id'] ?? null,
                    'recipient_id' => $data['recipient_id'] ?? $recipientId
                ];
            } else {
                $error = $response->json();
                $errorMessage = $this->extractErrorMessage($error);
                
                Log::error('Facebook message send failed', [
                    'recipient_id' => $recipientId,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Facebook message send exception', [
                'recipient_id' => $recipientId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send typing indicator to show bot is processing
     */
    public function sendTypingIndicator($recipientId)
    {
        try {
            if (empty($this->pageAccessToken)) {
                return false;
            }

            $payload = [
                'recipient' => [
                    'id' => $recipientId
                ],
                'sender_action' => 'typing_on'
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/me/messages?access_token=' . $this->pageAccessToken, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Facebook typing indicator failed', [
                'recipient_id' => $recipientId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get user profile information
     */
    public function getUserProfile($userId)
    {
        try {
            $url = "https://graph.facebook.com/v18.0/{$userId}";
            
            $response = Http::get($url, [
                'fields' => 'first_name,last_name,profile_pic',
                'access_token' => $this->pageAccessToken
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'profile' => $response->json()
                ];
            } else {
                Log::error('Failed to get user profile', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to get user profile'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception getting user profile', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send a media attachment (image, video, audio, file)
     */
    public function sendMediaAttachment($recipientId, $mediaType, $mediaUrl, $isReusable = true)
    {
        try {
            if (empty($this->pageAccessToken)) {
                throw new \Exception('Facebook page access token is not configured');
            }

            $payload = [
                'recipient' => [
                    'id' => $recipientId
                ],
                'messaging_type' => 'RESPONSE',
                'message' => [
                    'attachment' => [
                        'type' => $mediaType, // image, video, audio, file
                        'payload' => [
                            'url' => $mediaUrl,
                            'is_reusable' => $isReusable
                        ]
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/me/messages?access_token=' . $this->pageAccessToken, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Facebook media attachment sent successfully', [
                    'recipient_id' => $recipientId,
                    'media_type' => $mediaType,
                    'message_id' => $data['message_id'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'message_id' => $data['message_id'] ?? null,
                    'recipient_id' => $data['recipient_id'] ?? $recipientId
                ];
            } else {
                $error = $response->json();
                $errorMessage = $this->extractErrorMessage($error);
                
                Log::error('Facebook media attachment send failed', [
                    'recipient_id' => $recipientId,
                    'media_type' => $mediaType,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Facebook media attachment send exception', [
                'recipient_id' => $recipientId,
                'media_type' => $mediaType,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send multiple media attachments (images only, max 30)
     */
    public function sendMultipleMediaAttachments($recipientId, $imageUrls)
    {
        try {
            if (empty($this->pageAccessToken)) {
                throw new \Exception('Facebook page access token is not configured');
            }

            if (count($imageUrls) > 30) {
                throw new \Exception('Maximum 30 images allowed');
            }

            $attachments = [];
            foreach ($imageUrls as $imageUrl) {
                $attachments[] = [
                    'type' => 'image',
                    'payload' => [
                        'url' => $imageUrl
                    ]
                ];
            }

            $payload = [
                'recipient' => [
                    'id' => $recipientId
                ],
                'messaging_type' => 'RESPONSE',
                'message' => [
                    'attachments' => $attachments
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/me/messages?access_token=' . $this->pageAccessToken, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Facebook multiple media attachments sent successfully', [
                    'recipient_id' => $recipientId,
                    'image_count' => count($imageUrls),
                    'message_id' => $data['message_id'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'message_id' => $data['message_id'] ?? null,
                    'recipient_id' => $data['recipient_id'] ?? $recipientId
                ];
            } else {
                $error = $response->json();
                $errorMessage = $this->extractErrorMessage($error);
                
                Log::error('Facebook multiple media attachments send failed', [
                    'recipient_id' => $recipientId,
                    'image_count' => count($imageUrls),
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Facebook multiple media attachments send exception', [
                'recipient_id' => $recipientId,
                'image_count' => count($imageUrls ?? []),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send a reply to a specific message
     */
    public function sendReplyToMessage($recipientId, $message, $replyToMessageId)
    {
        try {
            if (empty($this->pageAccessToken)) {
                throw new \Exception('Facebook page access token is not configured');
            }

            $payload = [
                'recipient' => [
                    'id' => $recipientId
                ],
                'messaging_type' => 'RESPONSE',
                'message' => [
                    'text' => $message
                ],
                'reply_to' => [
                    'mid' => $replyToMessageId
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/me/messages?access_token=' . $this->pageAccessToken, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Facebook reply message sent successfully', [
                    'recipient_id' => $recipientId,
                    'reply_to_message_id' => $replyToMessageId,
                    'message_id' => $data['message_id'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'message_id' => $data['message_id'] ?? null,
                    'recipient_id' => $data['recipient_id'] ?? $recipientId
                ];
            } else {
                $error = $response->json();
                $errorMessage = $this->extractErrorMessage($error);
                
                Log::error('Facebook reply message send failed', [
                    'recipient_id' => $recipientId,
                    'reply_to_message_id' => $replyToMessageId,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Facebook reply message send exception', [
                'recipient_id' => $recipientId,
                'reply_to_message_id' => $replyToMessageId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract error message from API response
     */
    private function extractErrorMessage($error)
    {
        if (isset($error['error']['message'])) {
            return $error['error']['message'];
        } elseif (isset($error['message'])) {
            return $error['message'];
        } elseif (is_string($error)) {
            return $error;
        }
        
        return 'Unknown error';
    }

    /**
     * Send product card message with image, details, and buy button
     */
    public function sendProductCard($recipientId, array $product, $accessToken = null)
    {
        try {
            $token = $accessToken ?: $this->pageAccessToken;
            if (empty($token)) {
                throw new \Exception('Facebook page access token is not configured');
            }

            // Create product card template
            $elements = [[
                'title' => $product['name'],
                'subtitle' => $this->formatProductSubtitle($product),
                'image_url' => $product['image_url'] ?? null,
                'buttons' => [
                    [
                        'type' => 'web_url',
                        'url' => $product['product_url'],
                        'title' => 'Buy Now'
                    ]
                ]
            ]];

            // Remove image_url if not provided
            if (empty($product['image_url'])) {
                unset($elements[0]['image_url']);
            }

            $payload = [
                'recipient' => [
                    'id' => $recipientId
                ],
                'messaging_type' => 'RESPONSE',
                'message' => [
                    'attachment' => [
                        'type' => 'template',
                        'payload' => [
                            'template_type' => 'generic',
                            'elements' => $elements
                        ]
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/me/messages?access_token=' . $token, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Facebook product card sent successfully', [
                    'recipient_id' => $recipientId,
                    'product_id' => $product['id'] ?? null,
                    'message_id' => $data['message_id'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'message_id' => $data['message_id'] ?? null,
                    'recipient_id' => $data['recipient_id'] ?? $recipientId
                ];
            } else {
                $error = $response->json();
                $errorMessage = $this->extractErrorMessage($error);
                
                Log::error('Facebook product card send failed', [
                    'recipient_id' => $recipientId,
                    'product_id' => $product['id'] ?? null,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Facebook product card send exception', [
                'recipient_id' => $recipientId,
                'product_id' => $product['id'] ?? null,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send multiple product cards as carousel
     */
    public function sendProductCarousel($recipientId, array $products, $accessToken = null)
    {
        try {
            $token = $accessToken ?: $this->pageAccessToken;
            if (empty($token)) {
                throw new \Exception('Facebook page access token is not configured');
            }

            if (empty($products)) {
                throw new \Exception('No products provided for carousel');
            }

            // Limit to 10 products (Facebook limit)
            $products = array_slice($products, 0, 10);

            $elements = [];
            foreach ($products as $product) {
                $element = [
                    'title' => $product['name'],
                    'subtitle' => $this->formatProductSubtitle($product),
                    'buttons' => [
                        [
                            'type' => 'web_url',
                            'url' => $product['product_url'],
                            'title' => 'Buy Now'
                        ]
                    ]
                ];

                // Add image if available
                if (!empty($product['image_url'])) {
                    $element['image_url'] = $product['image_url'];
                }

                $elements[] = $element;
            }

            $payload = [
                'recipient' => [
                    'id' => $recipientId
                ],
                'messaging_type' => 'RESPONSE',
                'message' => [
                    'attachment' => [
                        'type' => 'template',
                        'payload' => [
                            'template_type' => 'generic',
                            'elements' => $elements
                        ]
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/me/messages?access_token=' . $token, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Facebook product carousel sent successfully', [
                    'recipient_id' => $recipientId,
                    'product_count' => count($products),
                    'message_id' => $data['message_id'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'message_id' => $data['message_id'] ?? null,
                    'recipient_id' => $data['recipient_id'] ?? $recipientId
                ];
            } else {
                $error = $response->json();
                $errorMessage = $this->extractErrorMessage($error);
                
                Log::error('Facebook product carousel send failed', [
                    'recipient_id' => $recipientId,
                    'product_count' => count($products),
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Facebook product carousel send exception', [
                'recipient_id' => $recipientId,
                'product_count' => count($products ?? []),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send quick reply message with predefined options
     */
    public function sendQuickReply($recipientId, $text, array $quickReplies, $accessToken = null)
    {
        try {
            $token = $accessToken ?: $this->pageAccessToken;
            if (empty($token)) {
                throw new \Exception('Facebook page access token is not configured');
            }

            $quickReplyButtons = [];
            foreach ($quickReplies as $reply) {
                $quickReplyButtons[] = [
                    'content_type' => 'text',
                    'title' => $reply['title'],
                    'payload' => $reply['payload'] ?? $reply['title']
                ];
            }

            $payload = [
                'recipient' => [
                    'id' => $recipientId
                ],
                'messaging_type' => 'RESPONSE',
                'message' => [
                    'text' => $text,
                    'quick_replies' => $quickReplyButtons
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/me/messages?access_token=' . $token, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Facebook quick reply sent successfully', [
                    'recipient_id' => $recipientId,
                    'message_id' => $data['message_id'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'message_id' => $data['message_id'] ?? null,
                    'recipient_id' => $data['recipient_id'] ?? $recipientId
                ];
            } else {
                $error = $response->json();
                $errorMessage = $this->extractErrorMessage($error);
                
                Log::error('Facebook quick reply send failed', [
                    'recipient_id' => $recipientId,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Facebook quick reply send exception', [
                'recipient_id' => $recipientId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format product subtitle for display
     */
    private function formatProductSubtitle(array $product): string
    {
        $subtitle = $product['price'];
        
        if (isset($product['in_stock']) && $product['in_stock']) {
            if (isset($product['stock_quantity']) && $product['stock_quantity'] > 0) {
                $subtitle .= " • In Stock ({$product['stock_quantity']} available)";
            } else {
                $subtitle .= " • In Stock";
            }
        } else {
            $subtitle .= " • Out of Stock";
        }

        if (!empty($product['description'])) {
            $subtitle .= "\n" . $product['description'];
        }

        return $subtitle;
    }

    /**
     * Check if Facebook service is properly configured
     */
    public function isConfigured()
    {
        return !empty($this->pageAccessToken);
    }
}