<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGPTService
{
    private $apiKey;
    private $model;
    private $maxTokens;
    private $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model');
        $this->maxTokens = (int) config('services.openai.max_tokens');
    }

    /**
     * Set configuration dynamically
     */
    public function setConfig($config)
    {
        if (isset($config['api_key'])) {
            $this->apiKey = $config['api_key'];
        }
        if (isset($config['model'])) {
            $this->model = $config['model'];
        }
        if (isset($config['max_tokens'])) {
            $this->maxTokens = (int) $config['max_tokens'];
        }
    }

    /**
     * Test the connection to OpenAI API
     */
    public function testConnection($config = null)
    {
        try {
            // Use provided config if available
            if ($config) {
                $this->setConfig($config);
            }

            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'message' => 'OpenAI API key is not configured'
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Hello, this is a test message.'
                    ]
                ],
                'max_tokens' => 10
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Connection successful!',
                    'model' => $this->model,
                    'response' => $data['choices'][0]['message']['content'] ?? 'Test successful'
                ];
            } else {
                $error = $response->json();
                $errorMessage = 'Unknown error';
                
                if (isset($error['error']['message'])) {
                    $errorMessage = $error['error']['message'];
                } elseif (isset($error['message'])) {
                    $errorMessage = $error['message'];
                } elseif (is_string($error)) {
                    $errorMessage = $error;
                }
                
                return [
                    'success' => false,
                    'message' => 'API Error: ' . $errorMessage,
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('ChatGPT connection test failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send a message to ChatGPT and get response
     */
    public function sendMessage($message, $systemPrompt = null)
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('OpenAI API key is not configured');
            }

            $messages = [];
            
            if ($systemPrompt) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemPrompt
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $this->maxTokens,
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => $data['choices'][0]['message']['content'],
                    'usage' => $data['usage'] ?? null
                ];
            } else {
                $error = $response->json();
                $errorMessage = 'Unknown API error';
                
                if (isset($error['error']['message'])) {
                    $errorMessage = $error['error']['message'];
                } elseif (isset($error['message'])) {
                    $errorMessage = $error['message'];
                } elseif (is_string($error)) {
                    $errorMessage = $error;
                }
                
                throw new \Exception($errorMessage);
            }
        } catch (\Exception $e) {
            Log::error('ChatGPT message failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get current configuration
     */
    public function getConfiguration()
    {
        return [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'api_key_configured' => !empty($this->apiKey),
            'api_key_preview' => $this->apiKey ? 'sk-...' . substr($this->apiKey, -4) : null
        ];
    }
}