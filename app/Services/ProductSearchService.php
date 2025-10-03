<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ProductSearchService
{
    private $imageProcessingService;

    public function __construct(ImageProcessingService $imageProcessingService)
    {
        $this->imageProcessingService = $imageProcessingService;
    }

    /**
     * Search for products based on image analysis
     */
    public function searchProductsByImage(array $imageAnalysis, int $userId, int $limit = 5): Collection
    {
        try {
            Log::info('Starting product search', [
                'user_id' => $userId,
                'analysis' => $imageAnalysis
            ]);

            // Get all products for the user
            $products = Product::where('user_id', $userId)
                ->where('in_stock', true)
                ->where('status', 'publish')
                ->with(['wooStore'])
                ->get();

            if ($products->isEmpty()) {
                Log::info('No products found for user', ['user_id' => $userId]);
                return collect();
            }

            // Score products based on similarity to image analysis
            $scoredProducts = $products->map(function ($product) use ($imageAnalysis) {
                $score = $this->calculateProductSimilarity($product, $imageAnalysis);
                return [
                    'product' => $product,
                    'score' => $score,
                    'match_reasons' => $this->getMatchReasons($product, $imageAnalysis)
                ];
            });

            // Sort by score and return top matches
            $topMatches = $scoredProducts
                ->sortByDesc('score')
                ->take($limit)
                ->filter(function ($item) {
                    return $item['score'] > 0.1; // Minimum similarity threshold
                });

            Log::info('Product search completed', [
                'total_products' => $products->count(),
                'matches_found' => $topMatches->count(),
                'top_score' => $topMatches->first()['score'] ?? 0
            ]);

            return $topMatches;

        } catch (\Exception $e) {
            Log::error('Product search failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Calculate similarity score between product and image analysis
     */
    private function calculateProductSimilarity(Product $product, array $imageAnalysis): float
    {
        $score = 0.0;
        $maxScore = 0.0;

        // Product name similarity (weight: 30%)
        $nameScore = $this->calculateTextSimilarity(
            $product->name,
            $imageAnalysis['description'] ?? ''
        );
        $score += $nameScore * 0.3;
        $maxScore += 0.3;

        // Description similarity (weight: 25%)
        if ($product->description) {
            $descScore = $this->calculateTextSimilarity(
                $product->description,
                $imageAnalysis['description'] ?? ''
            );
            $score += $descScore * 0.25;
        }
        $maxScore += 0.25;

        // Keyword matching (weight: 25%)
        $keywordScore = $this->calculateKeywordSimilarity(
            $product,
            $imageAnalysis['search_keywords'] ?? []
        );
        $score += $keywordScore * 0.25;
        $maxScore += 0.25;

        // Product type matching (weight: 10%)
        if (isset($imageAnalysis['product_type'])) {
            $typeScore = $this->calculateProductTypeMatch(
                $product,
                $imageAnalysis['product_type']
            );
            $score += $typeScore * 0.1;
        }
        $maxScore += 0.1;

        // Brand matching (weight: 10%)
        if (isset($imageAnalysis['brand']) && !empty($imageAnalysis['brand'])) {
            $brandScore = $this->calculateBrandMatch(
                $product,
                $imageAnalysis['brand']
            );
            $score += $brandScore * 0.1;
        }
        $maxScore += 0.1;

        // Normalize score
        return $maxScore > 0 ? $score / $maxScore : 0;
    }

    /**
     * Calculate text similarity using simple word matching
     */
    private function calculateTextSimilarity(string $text1, string $text2): float
    {
        if (empty($text1) || empty($text2)) {
            return 0.0;
        }

        $words1 = $this->extractWords($text1);
        $words2 = $this->extractWords($text2);

        if (empty($words1) || empty($words2)) {
            return 0.0;
        }

        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));

        return count($intersection) / count($union);
    }

    /**
     * Calculate keyword similarity
     */
    private function calculateKeywordSimilarity(Product $product, array $keywords): float
    {
        if (empty($keywords)) {
            return 0.0;
        }

        $productText = strtolower($product->name . ' ' . $product->description);
        $matchCount = 0;

        foreach ($keywords as $keyword) {
            if (strpos($productText, strtolower($keyword)) !== false) {
                $matchCount++;
            }
        }

        return $matchCount / count($keywords);
    }

    /**
     * Calculate product type match
     */
    private function calculateProductTypeMatch(Product $product, string $productType): float
    {
        $productText = strtolower($product->name . ' ' . $product->description);
        $type = strtolower($productType);

        // Direct match
        if (strpos($productText, $type) !== false) {
            return 1.0;
        }

        // Category mapping
        $categoryMappings = [
            'clothing' => ['shirt', 'dress', 'pants', 'jacket', 'sweater', 'top', 'bottom'],
            'electronics' => ['phone', 'laptop', 'computer', 'tablet', 'headphone', 'speaker'],
            'accessories' => ['bag', 'watch', 'jewelry', 'belt', 'hat', 'scarf'],
            'shoes' => ['shoe', 'boot', 'sneaker', 'sandal', 'heel'],
            'home' => ['furniture', 'decor', 'kitchen', 'bedroom', 'living'],
        ];

        foreach ($categoryMappings as $category => $items) {
            if ($type === $category || in_array($type, $items)) {
                foreach ($items as $item) {
                    if (strpos($productText, $item) !== false) {
                        return 0.8;
                    }
                }
            }
        }

        return 0.0;
    }

    /**
     * Calculate brand match
     */
    private function calculateBrandMatch(Product $product, string $brand): float
    {
        $productText = strtolower($product->name . ' ' . $product->description);
        $brandLower = strtolower($brand);

        if (strpos($productText, $brandLower) !== false) {
            return 1.0;
        }

        return 0.0;
    }

    /**
     * Extract words from text for comparison
     */
    private function extractWords(string $text): array
    {
        $words = str_word_count(strtolower($text), 1);
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        
        return array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
    }

    /**
     * Get reasons why products matched
     */
    private function getMatchReasons(Product $product, array $imageAnalysis): array
    {
        $reasons = [];

        // Check name similarity
        if ($this->calculateTextSimilarity($product->name, $imageAnalysis['description'] ?? '') > 0.3) {
            $reasons[] = 'Similar product name';
        }

        // Check keyword matches
        $keywords = $imageAnalysis['search_keywords'] ?? [];
        $productText = strtolower($product->name . ' ' . $product->description);
        $matchedKeywords = [];
        
        foreach ($keywords as $keyword) {
            if (strpos($productText, strtolower($keyword)) !== false) {
                $matchedKeywords[] = $keyword;
            }
        }

        if (!empty($matchedKeywords)) {
            $reasons[] = 'Matches keywords: ' . implode(', ', array_slice($matchedKeywords, 0, 3));
        }

        // Check product type
        if (isset($imageAnalysis['product_type'])) {
            $typeMatch = $this->calculateProductTypeMatch($product, $imageAnalysis['product_type']);
            if ($typeMatch > 0.5) {
                $reasons[] = 'Similar product category';
            }
        }

        // Check brand
        if (isset($imageAnalysis['brand']) && !empty($imageAnalysis['brand'])) {
            $brandMatch = $this->calculateBrandMatch($product, $imageAnalysis['brand']);
            if ($brandMatch > 0.5) {
                $reasons[] = 'Brand match: ' . $imageAnalysis['brand'];
            }
        }

        return $reasons;
    }

    /**
     * Get product images for display
     */
    public function getProductImages(Product $product, int $limit = 3): array
    {
        $images = ProductImage::where('product_id', $product->id)
            ->orderBy('position')
            ->limit($limit)
            ->pluck('src')
            ->toArray();

        // If no images found, try to extract from raw data
        if (empty($images) && $product->raw) {
            $rawData = $product->raw;
            if (isset($rawData['images']) && is_array($rawData['images'])) {
                $images = array_slice(
                    array_column($rawData['images'], 'src'),
                    0,
                    $limit
                );
            }
        }

        return $images;
    }

    /**
     * Format product for display in messenger
     */
    public function formatProductForMessenger(array $productMatch): array
    {
        $product = $productMatch['product'];
        $images = $this->getProductImages($product, 1);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $this->truncateText($product->description, 100),
            'price' => $product->formatted_price,
            'stock_quantity' => $product->stock_quantity,
            'in_stock' => $product->in_stock,
            'image_url' => $images[0] ?? null,
            'product_url' => $product->permalink ?? $product->product_url,
            'similarity_score' => round($productMatch['score'] * 100, 1),
            'match_reasons' => $productMatch['match_reasons'],
            'sku' => $product->sku
        ];
    }

    /**
     * Format multiple products for display in messenger (text-based search)
     */
    public function formatProductsForMessenger($products): string
    {
        if (!$products || $products->isEmpty()) {
            return "I couldn't find any products matching your search. Could you try a different search term?";
        }

        $count = $products->count();
        $message = "I found {$count} product" . ($count > 1 ? 's' : '') . " for you:\n\n";

        foreach ($products->take(5) as $index => $product) {
            $message .= ($index + 1) . ". {$product->name}\n";
            $message .= "   💰 Price: {$product->formatted_price}\n";
            
            if ($product->stock_quantity > 0) {
                $message .= "   ✅ In Stock ({$product->stock_quantity} available)\n";
            } else {
                $message .= "   ❌ Out of Stock\n";
            }
            
            if (!empty($product->description)) {
                $description = $this->truncateText($product->description, 80);
                $message .= "   📝 {$description}\n";
            }
            
            if (!empty($product->permalink) || !empty($product->product_url)) {
                $url = $product->permalink ?? $product->product_url;
                $message .= "   🔗 View Product: {$url}\n";
            }
            
            $message .= "\n";
        }

        if ($count > 5) {
            $message .= "... and " . ($count - 5) . " more products available.\n";
        }

        $message .= "Would you like more details about any of these products?";

        return $message;
    }

    /**
     * Search products by text query
     */
    public function searchProductsByText(string $query, int $userId)
    {
        try {
            Log::info('Searching products by text', [
                'query' => $query,
                'user_id' => $userId
            ]);

            // Get all products for the user
            $products = Product::where('user_id', $userId)
                ->where('status', 'publish')
                ->get();

            if ($products->isEmpty()) {
                Log::info('No products found for user', ['user_id' => $userId]);
                return collect();
            }

            $scoredProducts = [];
            $queryWords = $this->extractWords($query);

            foreach ($products as $product) {
                $score = $this->calculateTextBasedScore($product, $queryWords);
                
                if ($score > 0) {
                    $scoredProducts[] = [
                        'product' => $product,
                        'score' => $score,
                        'match_reasons' => $this->getTextMatchReasons($product, $queryWords)
                    ];
                }
            }

            // Sort by score (highest first)
            usort($scoredProducts, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Return top 5 products
            $topProducts = array_slice($scoredProducts, 0, 5);
            
            Log::info('Text search completed', [
                'query' => $query,
                'total_products' => $products->count(),
                'matching_products' => count($scoredProducts),
                'returned_products' => count($topProducts)
            ]);

            return collect(array_map(function ($item) {
                return $item['product'];
            }, $topProducts));

        } catch (\Exception $e) {
            Log::error('Error in text product search', [
                'query' => $query,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Calculate text-based score for product matching
     */
    private function calculateTextBasedScore(Product $product, array $queryWords): float
    {
        $score = 0;
        $productText = strtolower($product->name . ' ' . strip_tags($product->description ?? ''));
        $productWords = $this->extractWords($productText);
        
        // Exact name match (highest score)
        foreach ($queryWords as $queryWord) {
            if (strpos(strtolower($product->name), $queryWord) !== false) {
                $score += 2.0; // High score for name matches
            }
        }
        
        // Word intersection score
        $intersection = array_intersect($queryWords, $productWords);
        $score += count($intersection) * 1.0;
        
        // Partial word matches
        foreach ($queryWords as $queryWord) {
            foreach ($productWords as $productWord) {
                if (strpos($productWord, $queryWord) !== false || strpos($queryWord, $productWord) !== false) {
                    $score += 0.5;
                }
            }
        }
        
        return $score;
    }

    /**
     * Get text-based match reasons
     */
    private function getTextMatchReasons(Product $product, array $queryWords): array
    {
        $reasons = [];
        $productText = strtolower($product->name . ' ' . strip_tags($product->description ?? ''));
        
        // Check for exact matches in name
        foreach ($queryWords as $queryWord) {
            if (strpos(strtolower($product->name), $queryWord) !== false) {
                $reasons[] = "Name contains '{$queryWord}'";
            }
        }
        
        // Check for matches in description
        $descriptionMatches = [];
        foreach ($queryWords as $queryWord) {
            if (strpos($productText, $queryWord) !== false && 
                strpos(strtolower($product->name), $queryWord) === false) {
                $descriptionMatches[] = $queryWord;
            }
        }
        
        if (!empty($descriptionMatches)) {
            $reasons[] = "Description matches: " . implode(', ', array_slice($descriptionMatches, 0, 3));
        }
        
        return $reasons;
    }

    /**
     * Truncate text to specified length
     */
    private function truncateText(?string $text, int $length): string
    {
        if (!$text || strlen($text) <= $length) {
            return $text ?? '';
        }

        return substr($text, 0, $length) . '...';
    }
}