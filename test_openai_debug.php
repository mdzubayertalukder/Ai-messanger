<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ChatGPTService;

echo "=== OPENAI DEBUG REPORT ===\n";

// Check environment variables
echo "Environment variables:\n";
echo "OPENAI_API_KEY: " . (env('OPENAI_API_KEY') ? 'Set (length: ' . strlen(env('OPENAI_API_KEY')) . ')' : 'NOT SET') . "\n";
echo "OPENAI_MODEL: " . (env('OPENAI_MODEL') ?: 'NOT SET') . "\n";
echo "OPENAI_MAX_TOKENS: " . (env('OPENAI_MAX_TOKENS') ?: 'NOT SET') . "\n\n";

// Test ChatGPT service
$chatgptService = new ChatGPTService();

echo "ChatGPT Service Configuration:\n";
$config = $chatgptService->getConfiguration();
print_r($config);
echo "\n";

echo "Testing OpenAI connection...\n";
$testResult = $chatgptService->testConnection();
print_r($testResult);
echo "\n";

if ($testResult['success']) {
    echo "Testing message sending...\n";
    $messageResult = $chatgptService->sendMessage('Hello, this is a test message. Please respond with "Test successful".');
    print_r($messageResult);
} else {
    echo "Cannot test message sending due to connection failure.\n";
}