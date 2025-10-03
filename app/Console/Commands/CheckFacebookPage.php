<?php

namespace App\Console\Commands;

use App\Models\FacebookPage;
use Illuminate\Console\Command;

class CheckFacebookPage extends Command
{
    protected $signature = 'check:facebook-page';
    protected $description = 'Check Facebook page configuration';

    public function handle()
    {
        $this->info('🔍 Checking Facebook Page Configuration');
        $this->newLine();

        $page = FacebookPage::first();
        
        if (!$page) {
            $this->error('❌ No Facebook page found in database');
            return;
        }

        $this->info('✅ Facebook Page Found:');
        $this->line("   Page ID: {$page->page_id}");
        $this->line("   Page Name: {$page->page_name}");
        $this->line("   Subscribed: " . ($page->subscribed ? 'Yes' : 'No'));
        $this->line("   Access Token Length: " . strlen($page->access_token));
        $this->line("   User ID: {$page->user_id}");
        $this->line("   Created: {$page->created_at}");
        
        $this->newLine();
        
        // Check if access token looks valid
        if (strlen($page->access_token) < 50) {
            $this->warn('⚠️  Access token seems too short - might be invalid');
        } else {
            $this->info('✅ Access token length looks reasonable');
        }
        
        if (!$page->subscribed) {
            $this->warn('⚠️  Page is not subscribed to webhook events');
        } else {
            $this->info('✅ Page is subscribed to webhook events');
        }
    }
}