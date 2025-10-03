<?php

require_once 'vendor/autoload.php';

use App\Models\WooStore;
use Automattic\WooCommerce\Client as WooClient;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing WooCommerce API Connection...\n\n";

try {
    $stores = WooStore::all();
    echo "Found " . $stores->count() . " WooCommerce stores\n\n";
    
    foreach ($stores as $store) {
        echo "Testing Store: {$store->store_name}\n";
        echo "URL: {$store->store_url}\n";
        echo "Version: {$store->version}\n";
        echo "WP API: " . ($store->wp_api ? 'true' : 'false') . "\n";
        
        try {
            $client = new WooClient(
                rtrim($store->store_url, '/'),
                $store->consumer_key,
                $store->consumer_secret,
                [
                    'version' => $store->version ?? 'wc/v3',
                    'wp_api' => (bool) $store->wp_api,
                ]
            );
            
            // Test API connection
            $products = $client->get('products', ['per_page' => 5]);
            
            echo "✅ API Connection: SUCCESS\n";
            echo "Found " . count($products) . " products (first 5)\n";
            
            if (count($products) > 0) {
                echo "Sample product: " . ($products[0]->name ?? $products[0]['name'] ?? 'No name') . "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ API Connection: FAILED\n";
            echo "Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}