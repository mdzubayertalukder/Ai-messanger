<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;

class TestRealWebhook extends Command
{
    protected $signature = 'test:real-webhook {user_id} {message}';
    protected $description = 'Test webhook with a real user ID to verify complete flow';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $message = $this->argument('message');
        
        $this->info("Testing complete webhook flow with real user ID...");
        $this->info("User ID: {$userId}");
        $this->info("Message: {$message}");
        
        // Create a mock webhook request with real user ID
        $webhookData = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '100645845759962',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $userId],
                            'recipient' => ['id' => '100645845759962'],
                            'timestamp' => time() * 1000,
                            'message' => [
                                'mid' => 'real_message_' . time(),
                                'text' => $message
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        // Create a mock request
        $request = new Request();
        $request->merge($webhookData);
        
        // Process the webhook
        $controller = new WebhookController();
        $response = $controller->handleFacebook($request);
        
        $this->info("Webhook processed with status: " . $response->getStatusCode());
        
        // Wait a moment for job processing
        $this->info("Waiting for job processing...");
        sleep(2);
        
        // Run queue worker to process the job
        $this->info("Processing queue...");
        $exitCode = \Artisan::call('queue:work', ['--once' => true, '--timeout' => 30]);
        
        if ($exitCode === 0) {
            $this->info("✅ Queue job processed successfully!");
        } else {
            $this->error("❌ Queue job processing failed!");
        }
        
        // Check if message was stored
        $incomingMessage = \App\Models\Message::where('sender_id', $userId)
            ->where('direction', 'incoming')
            ->latest()
            ->first();
            
        if ($incomingMessage) {
            $this->info("✅ Incoming message stored: " . $incomingMessage->message_text);
        } else {
            $this->error("❌ No incoming message found!");
        }
        
        // Check if response was sent
        $outgoingMessage = \App\Models\Message::where('recipient_id', $userId)
            ->where('direction', 'outgoing')
            ->latest()
            ->first();
            
        if ($outgoingMessage) {
            $this->info("✅ Outgoing message sent: " . $outgoingMessage->message_text);
        } else {
            $this->error("❌ No outgoing message found!");
        }
        
        return 0;
    }
}