<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing AI Response Flow ===\n";

// Get the Facebook page from database
$facebookPage = App\Models\FacebookPage::first();

if (!$facebookPage) {
    echo "❌ No Facebook page found in database\n";
    exit(1);
}

echo "✅ Using Facebook Page ID: " . $facebookPage->page_id . "\n";

// Simulate a webhook message payload
$webhookData = [
    'object' => 'page',
    'entry' => [
        [
            'id' => $facebookPage->page_id,
            'time' => time(),
            'messaging' => [
                [
                    'sender' => [
                        'id' => '31561234136854048' // Real user ID from your test
                    ],
                    'recipient' => [
                        'id' => $facebookPage->page_id
                    ],
                    'timestamp' => time() * 1000,
                    'message' => [
                        'mid' => 'test_message_' . uniqid(),
                        'text' => 'Hello! Can you help me find a product?'
                    ]
                ]
            ]
        ]
    ]
];

echo "✅ Webhook data prepared\n";

// Dispatch the job
try {
    // Extract parameters from webhook data
    $senderId = '31561234136854048'; // Real user ID from your test
    $pageId = $facebookPage->page_id;
    $messageData = [
        'mid' => 'test_message_' . uniqid(),
        'text' => 'Hello! Can you help me find a product?'
    ];
    $pageAccessToken = $facebookPage->access_token;
    $facebookPageId = $facebookPage->id; // Database ID
    $userId = 1; // Assuming user ID 1 exists
    
    $job = new App\Jobs\ProcessMessengerMessage(
        $senderId,
        $pageId,
        $messageData,
        $pageAccessToken,
        $facebookPageId,
        $userId
    );
    
    echo "✅ Job created successfully\n";
    echo "🔄 Dispatching job to queue...\n";
    
    dispatch($job);
    
    echo "✅ Job dispatched to queue\n";
    echo "📊 Current queue status:\n";
    
    // Check queue status
    $queueJobs = DB::table('jobs')->count();
    echo "   - Jobs in queue: " . $queueJobs . "\n";
    
    if ($queueJobs > 0) {
        echo "🎯 Job is waiting to be processed. Run 'php artisan queue:work --once' to process it.\n";
    } else {
        echo "⚡ Job was processed immediately (sync queue)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error dispatching job: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";