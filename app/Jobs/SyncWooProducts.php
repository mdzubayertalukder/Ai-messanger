<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Automattic\WooCommerce\Client as WooClient;
use App\Models\{WooStore, Product, ProductImage, ProductVariant};
use Illuminate\Support\Facades\Log;

class SyncWooProducts implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public WooStore $store)
    {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $store = $this->store->fresh();
        $client = new WooClient(
            rtrim($store->store_url, '/'),
            $store->consumer_key,
            $store->consumer_secret,
            [
                'version' => $store->version ?? 'wc/v3',
                'wp_api' => (bool) $store->wp_api,
            ]
        );

        $page = 1;
        $perPage = 50;
        do {
            $products = $client->get('products', [
                'page' => $page,
                'per_page' => $perPage,
            ]);

            foreach ($products as $p) {
                $product = Product::updateOrCreate(
                    [
                        'woo_store_id' => $store->id,
                        'external_id' => (string)($p->id ?? ''),
                    ],
                    [
                        'user_id' => $store->user_id,
                        'name' => $p->name ?? '',
                        'description' => $p->description ?? null,
                        'sku' => $p->sku ?? null,
                        'price' => (float)($p->price ?? 0),
                        'stock_quantity' => isset($p->stock_quantity) ? (int)$p->stock_quantity : null,
                        'in_stock' => (bool)($p->in_stock ?? true),
                        'status' => $p->status ?? null,
                        'permalink' => $p->permalink ?? null,
                        'raw' => json_decode(json_encode($p), true),
                    ]
                );

                // Images
                if (!empty($p->images ?? [])) {
                    $images = $p->images ?? [];
                    foreach ($images as $position => $img) {
                        ProductImage::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'src' => $img->src ?? '',
                            ],
                            [
                                'position' => $position,
                            ]
                        );
                    }
                }

                // Variants
                if (!empty($p->variations ?? [])) {
                    $variants = $p->variations ?? [];
                    foreach ($variants as $v) {
                        ProductVariant::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'external_id' => (string)($v->id ?? ''),
                            ],
                            [
                                'sku' => $v->sku ?? null,
                                'attributes' => json_decode(json_encode($v->attributes ?? []), true),
                                'price' => (float)($v->price ?? 0),
                                'stock_quantity' => isset($v->stock_quantity) ? (int)$v->stock_quantity : null,
                                'in_stock' => (bool)($v->in_stock ?? true),
                            ]
                        );
                    }
                }
            }

            $count = is_array($products) ? count($products) : 0;
            $page++;
        } while ($count === $perPage);

        $store->update(['last_synced_at' => now()]);
    }
}
