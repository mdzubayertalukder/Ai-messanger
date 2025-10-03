<?php

namespace App\Jobs;

use App\Models\WooStore;
use App\Services\WooCommerceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWooCommerceData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $store;
    protected $syncType;

    /**
     * Create a new job instance.
     */
    public function __construct(WooStore $store, string $syncType = 'all')
    {
        $this->store = $store;
        $this->syncType = $syncType; // 'all', 'products', 'orders', 'customers'
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting WooCommerce sync', [
                'store_id' => $this->store->id,
                'store_name' => $this->store->store_name,
                'sync_type' => $this->syncType
            ]);

            $wooService = new WooCommerceService($this->store);

            $results = match($this->syncType) {
                'products' => ['products' => $wooService->syncProducts()],
                'orders' => ['orders' => $wooService->syncOrders()],
                'customers' => ['customers' => $wooService->syncCustomers()],
                default => $wooService->syncAll()
            };

            Log::info('WooCommerce sync completed', [
                'store_id' => $this->store->id,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('WooCommerce sync failed', [
                'store_id' => $this->store->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('WooCommerce sync job failed', [
            'store_id' => $this->store->id,
            'exception' => $exception->getMessage()
        ]);
    }
}
