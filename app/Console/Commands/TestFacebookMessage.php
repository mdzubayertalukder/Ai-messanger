<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FacebookService;

class TestFacebookMessage extends Command
{
    protected $signature = 'test:facebook-message {recipient_id} {message}';
    protected $description = 'Test sending a Facebook message to a real user ID';

    public function handle()
    {
        $recipientId = $this->argument('recipient_id');
        $message = $this->argument('message');
        
        // Use the access token from environment
        $accessToken = env('FACEBOOK_PAGE_ACCESS_TOKEN', 'EAFbmdgnkYXwBPtWQnrPT7SrodgFFLKjFN1L0aLzg6yf0AHtBJy8WUZBiZAEasmZCuP6hlyqrVIqVtdwxGDZB4QPndJoScwW8drHpmshwtrBikITW03XwmZC67eQIjRhCoXnGnPaRE9HjIt1XptFvmLyB2csVSZCKjrO4eQoPTPS7ZCEdEeAkjBZBwnPhjhHEgRQ75ZCA5');
        
        $this->info("Testing Facebook message send...");
        $this->info("Recipient ID: {$recipientId}");
        $this->info("Message: {$message}");
        $this->info("Access Token Length: " . strlen($accessToken));
        
        $facebookService = new FacebookService();
        $result = $facebookService->sendMessage($recipientId, $message, $accessToken);
        
        if ($result['success']) {
            $this->info("✅ Message sent successfully!");
            $this->info("Message ID: " . ($result['message_id'] ?? 'N/A'));
            $this->info("Recipient ID: " . ($result['recipient_id'] ?? 'N/A'));
        } else {
            $this->error("❌ Message send failed!");
            $this->error("Error: " . ($result['error'] ?? 'Unknown error'));
            $this->error("Status Code: " . ($result['status_code'] ?? 'N/A'));
        }
        
        return 0;
    }
}