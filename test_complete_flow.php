<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Jobs\ProcessMessengerMessage;
use App\Models\Message;
use App\Models\FacebookPage;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== COMPLETE MESSENGER FLOW TEST ===\n";

try {
    // Get or create a test Facebook page
    $facebookPage = FacebookPage::first();
    if (!$facebookPage) {
        echo "No Facebook page found. Creating test page...\n";
        $facebookPage = FacebookPage::create([
            'user_id' => 1,
            'page_id' => 'test_page_123',
            'page_name' => 'Test Store',
            'page_access_token' => 'EAABwzLixnjYBO123456789',  // Mock token for testing
            'is_active' => true
        ]);
    } else {
        // Update existing page with mock token for testing
        $facebookPage->update(['page_access_token' => 'EAABwzLixnjYBO123456789']);
    }

    echo "Using Facebook Page: {$facebookPage->page_name} (ID: {$facebookPage->id})\n\n";

    // Test different message scenarios
    $testMessages = [
        [
            'text' => 'Do you have mesh flower earrings?',
            'description' => 'Product search query'
        ],
        [
            'text' => 'Show me crystal earrings',
            'description' => 'Another product search'
        ],
        [
            'text' => 'What is your return policy?',
            'description' => 'General customer service question'
        ]
    ];

    foreach ($testMessages as $index => $testMessage) {
        echo "--- Test " . ($index + 1) . ": {$testMessage['description']} ---\n";
        echo "Message: \"{$testMessage['text']}\"\n";

        // Simulate messenger webhook data
        $messageData = [
            'sender' => ['id' => 'test_user_' . ($index + 1)],
            'recipient' => ['id' => $facebookPage->page_id],
            'timestamp' => time() * 1000,
            'text' => $testMessage['text'],  // Text should be at the root level
            'mid' => 'test_mid_' . ($index + 1)
        ];

        // Store incoming message
        $incomingMessage = Message::create([
            'user_id' => $facebookPage->user_id,
            'facebook_page_id' => $facebookPage->id,
            'sender_id' => $messageData['sender']['id'],
            'recipient_id' => $messageData['recipient']['id'],
            'message_text' => $testMessage['text'],
            'direction' => 'incoming',
            'responded_by_ai' => false,
        ]);

        echo "Incoming message stored (ID: {$incomingMessage->id})\n";

        // Process the message (simulate the job)
        echo "Processing message...\n";
        
        $job = new ProcessMessengerMessage(
            $messageData['sender']['id'],
            $facebookPage->page_id,
            $messageData,
            $facebookPage->page_access_token,
            $facebookPage->id,
            $facebookPage->user_id
        );

        // Execute the job directly (instead of dispatching to queue)
        $job->handle();

        // Check for outgoing messages
        $outgoingMessages = Message::where('sender_id', $facebookPage->page_id)
            ->where('recipient_id', $messageData['sender']['id'])
            ->where('direction', 'outgoing')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($outgoingMessages) {
            echo "✅ Response generated!\n";
            echo "Response: " . substr($outgoingMessages->message_text, 0, 200) . "...\n";
            echo "AI Response Type: " . ($outgoingMessages->ai_response ?? 'N/A') . "\n";
        } else {
            echo "❌ No response generated\n";
        }

        echo "\n";
    }

    echo "=== TEST COMPLETED ===\n";
    echo "Check the messages table for full details.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}