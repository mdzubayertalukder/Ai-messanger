<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Message;

echo "Messages in database: " . Message::count() . PHP_EOL;
echo PHP_EOL;

$messages = Message::latest()->take(5)->get(['id', 'sender_id', 'recipient_id', 'message_text', 'direction', 'user_id', 'facebook_page_id', 'created_at']);

foreach ($messages as $message) {
    echo "ID: {$message->id}" . PHP_EOL;
    echo "Sender: {$message->sender_id}" . PHP_EOL;
    echo "Recipient: {$message->recipient_id}" . PHP_EOL;
    echo "Direction: {$message->direction}" . PHP_EOL;
    echo "Text: " . substr($message->message_text, 0, 100) . "..." . PHP_EOL;
    echo "User ID: {$message->user_id}" . PHP_EOL;
    echo "Page ID: {$message->facebook_page_id}" . PHP_EOL;
    echo "Created: {$message->created_at}" . PHP_EOL;
    echo "---" . PHP_EOL;
}