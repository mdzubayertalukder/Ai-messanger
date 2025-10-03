<?php

namespace App\Console\Commands;

use App\Jobs\SyncWooCommerceData;
use App\Models\WooStore;
use Illuminate\Console\Command;

class SyncAllWooStores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woo:sync-all {--type=all : Type of sync (all, products, orders, customers)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all WooCommerce stores data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $syncType = $this->option('type');
        
        $stores = WooStore::all();
        
        if ($stores->isEmpty()) {
            $this->info('No WooCommerce stores found to sync.');
            return;
        }

        $this->info("Starting sync for {$stores->count()} stores (type: {$syncType})...");

        foreach ($stores as $store) {
            $this->line("Dispatching sync job for store: {$store->store_name}");
            SyncWooCommerceData::dispatch($store, $syncType);
        }

        $this->info('All sync jobs have been dispatched to the queue.');
    }
}
