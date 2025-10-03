<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMessengerMessage;
use App\Models\FacebookPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestJobDebug extends Command
{
    protected $signature = 'test:job-debug';
    protected $description = 'Debug the ProcessMessengerMessage job';

    public function handle()
    {
        $this->info('🔍 Debugging ProcessMessengerMessage job...');

        // Get a Facebook page
        $facebookPage = FacebookPage::first();
        if (!$facebookPage) {
            $this->error('No Facebook page found');
            return;
        }

        $this->info("Using Facebook page: {$facebookPage->page_name}");

        // Create minimal test data
        $messaging = [
            'sender' => ['id' => 'test_sender_123'],
            'recipient' => ['id' => $facebookPage->page_id],
            'timestamp' => now()->timestamp * 1000,
            'message' => [
                'text' => 'Test message',
                'attachments' => [
                    [
                        'type' => 'image',
                        'payload' => [
                            'url' => 'https://via.placeholder.com/400x400/0066CC/FFFFFF?text=Test+Image'
                        ]
                    ]
                ]
            ]
        ];

        $this->info('📝 Test data created');

        try {
            $this->info('🚀 Dispatching job synchronously...');
            
            // Dispatch synchronously to see the error immediately
            $job = new ProcessMessengerMessage(
                $messaging['sender']['id'],
                $facebookPage->page_id,
                $messaging,
                $facebookPage->page_access_token,
                $facebookPage->id,
                1 // user_id
            );

            $job->handle();
            
            $this->info('✅ Job completed successfully');
            
        } catch (\Exception $e) {
            $this->error('❌ Job failed with error:');
            $this->error($e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
        }
    }
}