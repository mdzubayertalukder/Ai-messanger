<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Message;
use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== RECENT MESSAGES CHECK ===\n";

try {
    // Get the most recent messages
    $recentMessages = Message::orderBy('created_at', 'desc')->take(10)->get();
    
    echo "Found {$recentMessages->count()} recent messages:\n\n";
    
    foreach ($recentMessages as $message) {
        echo "ID: {$message->id}\n";
        echo "Direction: {$message->direction}\n";
        echo "Sender: {$message->sender_id}\n";
        echo "Recipient: {$message->recipient_id}\n";
        echo "Text: " . substr($message->message_text, 0, 100) . "...\n";
        echo "AI Response: " . ($message->ai_response ?? 'None') . "\n";
        echo "Responded by AI: " . ($message->responded_by_ai ? 'Yes' : 'No') . "\n";
        echo "Created: {$message->created_at}\n";
        echo "---\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}