<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing ChatGPT Service ===\n\n";

try {
    // Test ChatGPT service
    $chatGPTService = app(App\Services\ChatGPTService::class);
    
    echo "✅ ChatGPT service instantiated successfully\n";
    
    // Test a simple message
    $testMessage = "Hello! Can you help me find a product?";
    echo "🔄 Sending test message: \"$testMessage\"\n";
    
    $response = $chatGPTService->sendMessage($testMessage);
    
    if ($response && isset($response['success']) && $response['success']) {
        echo "✅ ChatGPT Response received:\n";
        $message = $response['message'];
        echo "   \"" . substr($message, 0, 100) . (strlen($message) > 100 ? "..." : "") . "\"\n";
        echo "   Full response length: " . strlen($message) . " characters\n";
        
        if (isset($response['usage'])) {
            echo "   Tokens used: " . ($response['usage']['total_tokens'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ ChatGPT request failed\n";
        if (isset($response['message'])) {
            echo "   Error: " . $response['message'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error testing ChatGPT service: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";