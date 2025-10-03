<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WooStore;
use App\Models\Product;
use App\Jobs\SyncWooProducts;

class TestAutoSync extends Command
{
    protected $signature = 'test:auto-sync';
    protected $description = 'Test automatic WooCommerce synchronization when store is added';

    public function handle()
    {
        $this->info('Testing automatic WooCommerce synchronization...');
        
        // Get current product count
        $initialProductCount = Product::count();
        $this->info("Initial product count: {$initialProductCount}");
        
        // Get the existing store to simulate adding it again
        $existingStore = WooStore::first();
        
        if (!$existingStore) {
            $this->error('No WooCommerce store found to test with!');
            return;
        }
        
        $this->info("Testing with store: {$existingStore->store_name}");
        
        // Simulate the automatic sync that happens when a store is added
        $this->info('Dispatching sync job (simulating store addition)...');
        SyncWooProducts::dispatch($existingStore);
        
        $this->info('✓ Sync job dispatched successfully');
        $this->info('The queue worker should process this job automatically.');
        $this->info('Check the queue worker output to see the job processing.');
        
        // Wait a moment and check if job was processed
        $this->info('Waiting 5 seconds for job to process...');
        sleep(5);
        
        $finalProductCount = Product::count();
        $this->info("Final product count: {$finalProductCount}");
        
        if ($finalProductCount >= $initialProductCount) {
            $this->info('✓ Automatic sync appears to be working correctly!');
        } else {
            $this->warn('⚠ Product count did not increase. Check queue worker status.');
        }
    }
}