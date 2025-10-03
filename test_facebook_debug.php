<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FacebookPage;
use App\Services\FacebookService;

echo "=== FACEBOOK DEBUG REPORT ===\n";

// Check environment variables
echo "Environment variables:\n";
echo "FACEBOOK_APP_ID: " . (env('FACEBOOK_APP_ID') ? 'Set' : 'NOT SET') . "\n";
echo "FACEBOOK_APP_SECRET: " . (env('FACEBOOK_APP_SECRET') ? 'Set' : 'NOT SET') . "\n";
echo "FACEBOOK_VERIFY_TOKEN: " . (env('FACEBOOK_VERIFY_TOKEN') ? 'Set' : 'NOT SET') . "\n\n";

// Check Facebook pages in database
echo "Facebook pages in database:\n";
$facebookPages = FacebookPage::all();
echo "Total pages: " . $facebookPages->count() . "\n";

foreach ($facebookPages as $page) {
    echo "- Page ID: {$page->page_id}\n";
    echo "  Name: {$page->page_name}\n";
    echo "  Subscribed: " . ($page->subscribed ? 'Yes' : 'No') . "\n";
    echo "  Access Token: " . (strlen($page->access_token) > 10 ? 'Set (length: ' . strlen($page->access_token) . ')' : 'NOT SET') . "\n";
    echo "  User ID: {$page->user_id}\n";
    echo "\n";
}

// Test Facebook service
if ($facebookPages->count() > 0) {
    $testPage = $facebookPages->first();
    echo "Testing Facebook service with page: {$testPage->page_name}\n";
    
    $facebookService = new FacebookService();
    
    // Test sending a message to a test recipient
    echo "Testing message send (this will fail if recipient doesn't exist, but we can see the error):\n";
    $result = $facebookService->sendMessage('test_recipient_123', 'Test message from debug script', $testPage->access_token);
    print_r($result);
} else {
    echo "No Facebook pages found to test with.\n";
}