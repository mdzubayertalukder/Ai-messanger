<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== AI Response Test Results ===\n\n";

// Check failed jobs
$failedJobs = DB::table('failed_jobs')->count();
echo "❌ Failed jobs: " . $failedJobs . "\n";

if ($failedJobs > 0) {
    echo "⚠️  There are failed jobs. Checking latest failure...\n";
    $latestFailed = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->first();
    if ($latestFailed) {
        echo "   Failed at: " . $latestFailed->failed_at . "\n";
        $exception = json_decode($latestFailed->exception, true);
        if (isset($exception['message'])) {
            echo "   Error: " . $exception['message'] . "\n";
        }
    }
}

echo "\n";

// Check recent messages
echo "📨 Recent messages (last 5):\n";
$messages = App\Models\Message::orderBy('created_at', 'desc')->take(5)->get();

if ($messages->count() == 0) {
    echo "   No messages found.\n";
} else {
    foreach ($messages as $message) {
        $direction = $message->is_from_user ? "👤 User" : "🤖 AI";
        $text = substr($message->message_text, 0, 50) . (strlen($message->message_text) > 50 ? "..." : "");
        echo "   " . $direction . ": " . $text . " (" . $message->created_at . ")\n";
    }
}

echo "\n=== Test Complete ===\n";