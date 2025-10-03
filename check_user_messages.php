<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking User Messages ===\n\n";

// Get recent messages ordered by creation time
$messages = DB::table('messages')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "📨 Last 10 messages (all types):\n";
foreach ($messages as $message) {
    $direction = $message->direction === 'inbound' ? '👤 User' : '🤖 AI';
    $truncatedText = strlen($message->message_text) > 50 
        ? substr($message->message_text, 0, 50) . '...' 
        : $message->message_text;
    
    echo "   {$direction}: {$truncatedText} ({$message->created_at})\n";
}

echo "\n";

// Check for inbound messages without AI responses
$inboundMessages = DB::table('messages')
    ->where('direction', 'inbound')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "👤 Recent inbound messages:\n";
if ($inboundMessages->isEmpty()) {
    echo "   No inbound messages found in database\n";
} else {
    foreach ($inboundMessages as $inboundMessage) {
        $truncatedText = strlen($inboundMessage->message_text) > 50 
            ? substr($inboundMessage->message_text, 0, 50) . '...' 
            : $inboundMessage->message_text;
        
        echo "   User: {$truncatedText} ({$inboundMessage->created_at})\n";
        echo "   Responded by AI: " . ($inboundMessage->responded_by_ai ? 'Yes' : 'No') . "\n";
        
        if ($inboundMessage->ai_response) {
            $aiResponseText = strlen($inboundMessage->ai_response) > 50 
                ? substr($inboundMessage->ai_response, 0, 50) . '...' 
                : $inboundMessage->ai_response;
            echo "   AI Response: {$aiResponseText}\n";
        }
        
        echo "\n";
    }
}

echo "\n=== Check Complete ===\n";