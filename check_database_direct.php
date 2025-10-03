<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Message;

echo "=== Direct Database Query ===\n\n";

// Get all messages ordered by created_at desc
$messages = Message::orderBy('created_at', 'desc')->limit(15)->get();

echo "📊 Last 15 messages from database:\n";
foreach ($messages as $message) {
    $direction = $message->direction ?? 'NULL';
    $messageText = substr($message->message_text ?? '', 0, 50);
    $createdAt = $message->created_at;
    $senderId = $message->sender_id;
    $recipientId = $message->recipient_id;
    
    echo "   Direction: {$direction} | From: {$senderId} | To: {$recipientId} | Text: {$messageText} | Time: {$createdAt}\n";
}

echo "\n📈 Direction counts:\n";
$directionCounts = Message::selectRaw('direction, COUNT(*) as count')
    ->groupBy('direction')
    ->get();

foreach ($directionCounts as $count) {
    echo "   {$count->direction}: {$count->count} messages\n";
}

echo "\n🔍 Recent messages by direction:\n";

// Check incoming messages
$incomingMessages = Message::where('direction', 'incoming')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "   Incoming messages: " . $incomingMessages->count() . "\n";
foreach ($incomingMessages as $msg) {
    echo "     - {$msg->message_text} ({$msg->created_at})\n";
}

// Check outgoing messages  
$outgoingMessages = Message::where('direction', 'outgoing')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "   Outgoing messages: " . $outgoingMessages->count() . "\n";
foreach ($outgoingMessages as $msg) {
    echo "     - {$msg->message_text} ({$msg->created_at})\n";
}

echo "\n=== Query Complete ===\n";