<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ProductSearchService;
use App\Services\ImageProcessingService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRODUCT SEARCH TEST ===\n";

try {
    $imageProcessingService = new ImageProcessingService();
    $productSearchService = new ProductSearchService($imageProcessingService);
    
    // Test queries
    $testQueries = [
        "Mesh flower earrings",
        "earrings",
        "flower",
        "mesh",
        "bangles",
        "gold plated",
        "crystal earrings"
    ];
    
    foreach ($testQueries as $query) {
        echo "\n--- Testing query: '$query' ---\n";
        
        $results = $productSearchService->searchProductsByText($query, 2); // User ID 2
        
        if ($results && $results->isNotEmpty()) {
            echo "Found {$results->count()} products:\n";
            foreach ($results as $product) {
                echo "- {$product->name} (ID: {$product->id}, Price: {$product->formatted_price})\n";
            }
            
            // Test formatting for messenger
            echo "\nFormatted for Messenger:\n";
            $formatted = $productSearchService->formatProductsForMessenger($results);
            echo $formatted . "\n";
        } else {
            echo "No products found.\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}