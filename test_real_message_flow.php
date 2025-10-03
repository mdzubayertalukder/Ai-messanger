<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Jobs\ProcessMessengerMessage;
use App\Models\FacebookPage;
use App\Models\Message;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING REAL MESSAGE FLOW ===\n\n";

try {
    // Get or create a test Facebook page
    $facebookPage = FacebookPage::where('page_id', '100645845759962')->first();
    if (!$facebookPage) {
        echo "Facebook page not found. Creating test page...\n";
        $facebookPage = FacebookPage::create([
            'user_id' => 2, // Use user ID 2 which has products
            'page_id' => '100645845759962',
            'page_name' => 'Test Page',
            'page_access_token' => 'test_token_123',
            'webhook_verify_token' => 'test_verify',
        ]);
    } else {
        echo "Using existing Facebook page: {$facebookPage->page_name}\n";
    }
    
    echo "Page User ID: {$facebookPage->user_id}\n";
    echo "Page Access Token: {$facebookPage->access_token}\n\n";
    
    // Clear any existing messages for clean test
    Message::where('sender_id', 'test_sender_123')->delete();
    
    // Test message data (matching webhook structure)
    $messageData = [
        'mid' => 'test_mid_' . time(),
        'text' => 'Do you have Flower crystal ring?'
    ];
    
    echo "1. Creating ProcessMessengerMessage job...\n";
    $job = new ProcessMessengerMessage(
        'test_sender_123',
        $facebookPage->page_id, 
        $messageData,
        $facebookPage->access_token,
        $facebookPage->id, // Use database ID, not page_id
        $facebookPage->user_id
    );
    
    echo "2. Processing job synchronously...\n";
    $job->handle();
    
    echo "3. Checking for stored messages...\n";
    $incomingMessage = Message::where('sender_id', 'test_sender_123')
                             ->where('direction', 'incoming')
                             ->latest()
                             ->first();
    
    if ($incomingMessage) {
        echo "✅ Incoming message stored:\n";
        echo "   Text: {$incomingMessage->message_text}\n";
        echo "   AI Response: " . ($incomingMessage->ai_response ?? 'None') . "\n";
        echo "   Responded by AI: " . ($incomingMessage->responded_by_ai ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ No incoming message found\n";
    }
    
    $outgoingMessage = Message::where('recipient_id', 'test_sender_123')
                             ->where('direction', 'outgoing')
                             ->latest()
                             ->first();
    
    if ($outgoingMessage) {
        echo "✅ Outgoing message stored:\n";
        echo "   Text: " . substr($outgoingMessage->message_text, 0, 200) . "...\n";
        echo "   Responded by AI: " . ($outgoingMessage->responded_by_ai ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ No outgoing message found\n";
    }
    
    echo "\n4. Checking recent Laravel logs for this test...\n";
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $recentLogs = array_slice(explode("\n", $logs), -20);
        foreach ($recentLogs as $log) {
            if (strpos($log, 'test_sender_123') !== false || 
                strpos($log, 'Flower crystal ring') !== false ||
                strpos($log, 'searchProductsByText') !== false ||
                strpos($log, 'Products found') !== false) {
                echo "   " . $log . "\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}