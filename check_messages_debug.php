<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Message;

echo "=== MESSAGE DEBUG REPORT ===\n";
echo "Total messages in database: " . Message::count() . "\n\n";

echo "Latest 5 messages:\n";
$messages = Message::latest()->take(5)->get();

if ($messages->count() > 0) {
    foreach ($messages as $message) {
        echo "ID: {$message->id}\n";
        echo "Sender: {$message->sender_id}\n";
        echo "Direction: {$message->direction}\n";
        echo "Text: " . substr($message->message_text ?? 'No text', 0, 100) . "\n";
        echo "Created: {$message->created_at}\n";
        echo "AI Response: " . ($message->responded_by_ai ? 'Yes' : 'No') . "\n";
        echo "---\n";
    }
} else {
    echo "No messages found in database!\n";
}

echo "\nChecking for messages from sender '6896553293709647':\n";
$senderMessages = Message::where('sender_id', '6896553293709647')->get();
echo "Found " . $senderMessages->count() . " messages from this sender\n";

foreach ($senderMessages as $msg) {
    echo "- {$msg->direction}: {$msg->message_text} ({$msg->created_at})\n";
}