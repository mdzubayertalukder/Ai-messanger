<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Product;
use App\Services\ProductSearchService;
use App\Services\ImageProcessingService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING FLOWER CRYSTAL RING SEARCH ===\n\n";

try {
    // Check what products exist that might match
    echo "1. Checking all products in database:\n";
    $allProducts = Product::all();
    echo "Total products: " . $allProducts->count() . "\n\n";
    
    foreach ($allProducts as $product) {
        echo "- {$product->name} (ID: {$product->id}, User: {$product->user_id})\n";
        if (!empty($product->description)) {
            echo "  Description: " . substr($product->description, 0, 100) . "...\n";
        }
    }
    
    echo "\n2. Searching for products with 'flower', 'crystal', or 'ring' keywords:\n";
    $matchingProducts = Product::where(function($query) {
        $query->where('name', 'LIKE', '%flower%')
              ->orWhere('name', 'LIKE', '%crystal%')
              ->orWhere('name', 'LIKE', '%ring%')
              ->orWhere('description', 'LIKE', '%flower%')
              ->orWhere('description', 'LIKE', '%crystal%')
              ->orWhere('description', 'LIKE', '%ring%');
    })->get();
    
    echo "Found {$matchingProducts->count()} products matching keywords:\n";
    foreach ($matchingProducts as $product) {
        echo "- {$product->name} (ID: {$product->id}, User: {$product->user_id})\n";
        if (!empty($product->description)) {
            echo "  Description: " . substr($product->description, 0, 100) . "...\n";
        }
    }
    
    echo "\n3. Testing ProductSearchService with 'Flower crystal ring':\n";
    $imageProcessingService = new ImageProcessingService();
    $productSearchService = new ProductSearchService($imageProcessingService);
    
    // Test with different user IDs to see if there are products
    $userIds = Product::distinct()->pluck('user_id');
    echo "Testing with user IDs: " . $userIds->implode(', ') . "\n\n";
    
    foreach ($userIds as $userId) {
        echo "Testing with User ID: {$userId}\n";
        $searchResults = $productSearchService->searchProductsByText('Flower crystal ring', $userId);
        echo "Found {$searchResults->count()} products for user {$userId}\n";
        
        if ($searchResults->count() > 0) {
            foreach ($searchResults as $product) {
                echo "- {$product->name} (Score would be calculated)\n";
            }
            
            // Test formatting
            $formattedResponse = $productSearchService->formatProductsForMessenger($searchResults);
            echo "\nFormatted response:\n";
            echo $formattedResponse . "\n";
        }
        echo "\n";
    }
    
    echo "\n4. Testing with exact query 'Do you have Flower crystal ring?':\n";
    foreach ($userIds as $userId) {
        echo "Testing with User ID: {$userId}\n";
        $searchResults = $productSearchService->searchProductsByText('Do you have Flower crystal ring?', $userId);
        echo "Found {$searchResults->count()} products for user {$userId}\n";
        
        if ($searchResults->count() > 0) {
            $formattedResponse = $productSearchService->formatProductsForMessenger($searchResults);
            echo "Formatted response:\n";
            echo $formattedResponse . "\n";
        }
        echo "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}