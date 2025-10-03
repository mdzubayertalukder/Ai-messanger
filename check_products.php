<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;

echo "=== PRODUCT DATABASE CHECK ===\n";

$totalProducts = Product::count();
echo "Total products in database: $totalProducts\n\n";

if ($totalProducts > 0) {
    echo "Sample products:\n";
    $products = Product::limit(3)->get();
    
    foreach ($products as $product) {
        echo "---\n";
        echo "ID: {$product->id}\n";
        echo "Name: {$product->name}\n";
        echo "Price: {$product->price}\n";
        echo "Formatted Price: {$product->formatted_price}\n";
        echo "User ID: {$product->user_id}\n";
        echo "In Stock: " . ($product->in_stock ? 'Yes' : 'No') . "\n";
        echo "Status: {$product->status}\n";
        echo "Description: " . substr($product->description ?? '', 0, 100) . "...\n";
        
        // Check for images
        $images = ProductImage::where('product_id', $product->id)->get();
        echo "Images: " . $images->count() . "\n";
        if ($images->count() > 0) {
            echo "First image: " . $images->first()->src . "\n";
        }
        echo "\n";
    }
    
    // Check for products that might match "mesh flower earrings"
    echo "=== SEARCHING FOR EARRINGS ===\n";
    $earrings = Product::where('name', 'LIKE', '%earring%')
        ->orWhere('name', 'LIKE', '%flower%')
        ->orWhere('name', 'LIKE', '%mesh%')
        ->orWhere('description', 'LIKE', '%earring%')
        ->orWhere('description', 'LIKE', '%flower%')
        ->orWhere('description', 'LIKE', '%mesh%')
        ->get();
    
    echo "Found {$earrings->count()} products matching earring/flower/mesh keywords:\n";
    foreach ($earrings as $earring) {
        echo "- {$earring->name} (ID: {$earring->id}, User: {$earring->user_id})\n";
    }
} else {
    echo "No products found in database.\n";
}