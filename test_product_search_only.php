<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ProductSearchService;
use App\Services\ImageProcessingService;
use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRODUCT SEARCH LOGIC TEST ===\n";

try {
    $imageProcessingService = new ImageProcessingService();
    $productSearchService = new ProductSearchService($imageProcessingService);
    
    // Test the exact query from our test
    $query = "Do you have mesh flower earrings?";
    $userId = 2;
    
    echo "Testing query: '$query'\n";
    echo "User ID: $userId\n\n";
    
    // Search for products
    $matchingProducts = $productSearchService->searchProductsByText($query, $userId);
    
    if ($matchingProducts && $matchingProducts->isNotEmpty()) {
        echo "✅ Found {$matchingProducts->count()} products:\n";
        
        foreach ($matchingProducts as $index => $product) {
            echo ($index + 1) . ". {$product->name} (ID: {$product->id})\n";
            echo "   Price: {$product->formatted_price}\n";
            echo "   Stock: {$product->stock_quantity}\n";
            echo "   In Stock: " . ($product->in_stock ? 'Yes' : 'No') . "\n";
            echo "\n";
        }
        
        // Test formatting for messenger
        echo "--- Formatted Response ---\n";
        $formattedResponse = $productSearchService->formatProductsForMessenger($matchingProducts);
        echo $formattedResponse . "\n";
        
        echo "\n✅ Product search and formatting working correctly!\n";
        
    } else {
        echo "❌ No products found\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}