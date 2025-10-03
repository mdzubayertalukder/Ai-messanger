<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageProcessingService
{
    private $openaiApiKey;
    private $maxImageSize = 2048; // Max dimension for processing
    private $supportedFormats = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
    }

    /**
     * Download and process image from Facebook URL
     */
    public function downloadAndProcessImage(string $imageUrl, string $accessToken): ?array
    {
        try {
            // Download image from Facebook
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->get($imageUrl);

            if (!$response->successful()) {
                Log::error('Failed to download image from Facebook', [
                    'url' => $imageUrl,
                    'status' => $response->status()
                ]);
                return null;
            }

            $imageData = $response->body();
            $imageInfo = getimagesizefromstring($imageData);
            
            if (!$imageInfo) {
                Log::error('Invalid image data received');
                return null;
            }

            // Validate image format
            $mimeType = $imageInfo['mime'];
            $extension = $this->getExtensionFromMimeType($mimeType);
            
            if (!in_array($extension, $this->supportedFormats)) {
                Log::error('Unsupported image format', ['mime_type' => $mimeType]);
                return null;
            }

            // Process and resize image if needed
            $processedImage = $this->processImage($imageData);
            
            if (!$processedImage) {
                return null;
            }

            // Store processed image temporarily
            $filename = 'temp_images/' . uniqid() . '.' . $extension;
            Storage::disk('local')->put($filename, $processedImage);

            return [
                'local_path' => $filename,
                'original_url' => $imageUrl,
                'mime_type' => $mimeType,
                'size' => strlen($processedImage),
                'dimensions' => [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Image processing failed', [
                'url' => $imageUrl,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Process image: resize, optimize, and prepare for AI analysis
     */
    private function processImage(string $imageData): ?string
    {
        try {
            $image = Image::make($imageData);
            
            // Get current dimensions
            $width = $image->width();
            $height = $image->height();
            
            // Resize if image is too large
            if ($width > $this->maxImageSize || $height > $this->maxImageSize) {
                $image->resize($this->maxImageSize, $this->maxImageSize, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Convert to JPEG for consistency and smaller size
            $image->encode('jpg', 85);
            
            return $image->getEncoded();

        } catch (\Exception $e) {
            Log::error('Image processing failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Analyze image using OpenAI Vision API to extract product features
     */
    public function analyzeImageForProducts(string $imagePath): ?array
    {
        try {
            if (!$this->openaiApiKey) {
                Log::error('OpenAI API key not configured');
                return null;
            }

            $imageData = Storage::disk('local')->get($imagePath);
            $base64Image = base64_encode($imageData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4-vision-preview',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Analyze this image and extract product information. Identify the main product(s), describe their key features, colors, materials, style, and any text/brand names visible. Focus on details that would help match this to an e-commerce product catalog. Return the analysis in JSON format with keys: product_type, description, colors, materials, style, brand, key_features, and search_keywords.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,' . $base64Image
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 500
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI Vision API failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '';

            // Try to extract JSON from the response
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                $analysis = json_decode($jsonString, true);
                
                if ($analysis) {
                    return $analysis;
                }
            }

            // Fallback: create structured data from text response
            return [
                'product_type' => 'unknown',
                'description' => $content,
                'colors' => [],
                'materials' => [],
                'style' => '',
                'brand' => '',
                'key_features' => [],
                'search_keywords' => $this->extractKeywords($content)
            ];

        } catch (\Exception $e) {
            Log::error('Image analysis failed', [
                'path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extract keywords from text for product search
     */
    private function extractKeywords(string $text): array
    {
        // Simple keyword extraction
        $words = str_word_count(strtolower($text), 1);
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those'];
        
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });

        return array_values(array_unique($keywords));
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        return $mimeMap[$mimeType] ?? 'jpg';
    }

    /**
     * Clean up temporary image files
     */
    public function cleanupTempImage(string $imagePath): void
    {
        try {
            if (Storage::disk('local')->exists($imagePath)) {
                Storage::disk('local')->delete($imagePath);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup temp image', [
                'path' => $imagePath,
                'error' => $e->getMessage()
            ]);
        }
    }
}