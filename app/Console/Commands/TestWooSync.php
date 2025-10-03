<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WooStore;
use App\Jobs\SyncWooProducts;

class TestWooSync extends Command
{
    protected $signature = 'test:woo-sync {--sync : Run sync synchronously}';
    protected $description = 'Test WooCommerce synchronization';

    public function handle()
    {
        $this->info('Testing WooCommerce synchronization...');
        
        $stores = WooStore::all();
        
        if ($stores->isEmpty()) {
            $this->error('No WooCommerce stores found!');
            return;
        }
        
        foreach ($stores as $store) {
            $this->info("Testing sync for store: {$store->store_name}");
            $this->info("Store URL: {$store->store_url}");
            $this->info("Consumer Key: " . substr($store->consumer_key, 0, 10) . "...");
            
            if ($this->option('sync')) {
                $this->info('Running sync synchronously...');
                try {
                    $job = new SyncWooProducts($store);
                    $job->handle();
                    $this->info("✓ Sync completed successfully");
                } catch (\Exception $e) {
                    $this->error("✗ Sync failed: " . $e->getMessage());
                    $this->error("Stack trace: " . $e->getTraceAsString());
                }
            } else {
                try {
                    // Dispatch the sync job
                    SyncWooProducts::dispatch($store);
                    $this->info("✓ Sync job dispatched successfully");
                } catch (\Exception $e) {
                    $this->error("✗ Failed to dispatch sync job: " . $e->getMessage());
                }
            }
        }
        
        if (!$this->option('sync')) {
            $this->info('Test completed. Check queue worker for job processing.');
        }
    }
}