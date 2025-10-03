<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\WebhookController;

class TestFacebookWebhook extends Command
{
    protected $signature = 'test:facebook-webhook';
    protected $description = 'Test Facebook webhook with simulated message';

    public function handle()
    {
        $this->info('🧪 Testing Facebook Webhook with Simulated Message');
        $this->newLine();

        // Get the Facebook page from database
        $facebookPage = \App\Models\FacebookPage::first();
        
        if (!$facebookPage) {
            $this->error('❌ No Facebook page found');
            return;
        }

        $this->info("✅ Using Facebook page: {$facebookPage->page_name}");
        
        // Create simulated Facebook webhook payload
        $webhookPayload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $facebookPage->page_id,
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => 'test_user_' . time()],
                            'recipient' => ['id' => $facebookPage->page_id],
                            'timestamp' => time() * 1000,
                            'message' => [
                                'mid' => 'test_message_' . time(),
                                'text' => 'Hello! Can you help me find a product?'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->info('📤 Simulating Facebook webhook POST request...');
        $this->line('Payload: ' . json_encode($webhookPayload, JSON_PRETTY_PRINT));
        $this->newLine();

        // Create a request object
        $request = new Request();
        $request->replace($webhookPayload);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Hub-Signature-256', 'sha256=test_signature');

        // Call the webhook controller
        $controller = new WebhookController();
        
        try {
            $response = $controller->handleFacebook($request);
            
            $this->info('✅ Webhook processed successfully');
            $this->line("Response Status: {$response->getStatusCode()}");
            $this->line("Response Content: {$response->getContent()}");
            
            $this->newLine();
            $this->info('🔍 Checking if job was dispatched...');
            
            // Check if job was created
            $jobCount = \DB::table('jobs')->count();
            $this->line("Jobs in queue: {$jobCount}");
            
            if ($jobCount > 0) {
                $this->info('✅ Job was dispatched to queue');
                $this->ask('Press Enter to process the job...');
                
                // Process the job
                $this->call('queue:work', ['--once' => true, '--timeout' => 30]);
                
                $this->newLine();
                $this->info('🔍 Checking results...');
                
                // Check if message was stored
                $messageCount = \App\Models\Message::count();
                $this->line("Total messages in database: {$messageCount}");
                
                $latestMessage = \App\Models\Message::latest()->first();
                if ($latestMessage) {
                    $this->info('✅ Latest message found:');
                    $this->line("   Text: {$latestMessage->message_text}");
                    $this->line("   Direction: {$latestMessage->direction}");
                    $this->line("   Processed: " . ($latestMessage->processed ? 'Yes' : 'No'));
                }
            } else {
                $this->warn('⚠️  No job was dispatched - check webhook logic');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Webhook processing failed:');
            $this->error($e->getMessage());
            $this->line($e->getTraceAsString());
        }
    }
}