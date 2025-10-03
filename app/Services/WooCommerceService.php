<?php

namespace App\Services;

use App\Models\WooStore;
use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WooCommerceService
{
    protected $store;
    protected $baseUrl;
    protected $auth;

    public function __construct(WooStore $store)
    {
        $this->store = $store;
        $this->baseUrl = rtrim($store->store_url, '/') . '/wp-json/wc/v3/';
        $this->auth = [
            'username' => $store->consumer_key,
            'password' => $store->consumer_secret
        ];
    }

    /**
     * Sync all data for a store
     */
    public function syncAll(): array
    {
        $results = [
            'products' => $this->syncProducts(),
            'customers' => $this->syncCustomers(),
            'orders' => $this->syncOrders(),
        ];

        $this->store->update(['last_synced_at' => now()]);

        return $results;
    }

    /**
     * Sync products from WooCommerce
     */
    public function syncProducts(): array
    {
        $page = 1;
        $perPage = 100;
        $totalSynced = 0;
        $errors = [];

        do {
            try {
                $response = Http::withBasicAuth($this->auth['username'], $this->auth['password'])
                    ->get($this->baseUrl . 'products', [
                        'page' => $page,
                        'per_page' => $perPage,
                        'status' => 'any'
                    ]);

                if (!$response->successful()) {
                    $errors[] = "Failed to fetch products page {$page}: " . $response->body();
                    break;
                }

                $products = $response->json();
                
                if (empty($products)) {
                    break;
                }

                foreach ($products as $productData) {
                    $this->syncProduct($productData);
                    $totalSynced++;
                }

                $page++;
            } catch (\Exception $e) {
                $errors[] = "Error syncing products page {$page}: " . $e->getMessage();
                Log::error('WooCommerce product sync error', [
                    'store_id' => $this->store->id,
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                break;
            }
        } while (count($products) === $perPage);

        return [
            'synced' => $totalSynced,
            'errors' => $errors
        ];
    }

    /**
     * Sync single product
     */
    protected function syncProduct(array $productData): void
    {
        $productUrl = $productData['permalink'] ?? null;
        
        Product::updateOrCreate(
            [
                'woo_store_id' => $this->store->id,
                'external_id' => $productData['id']
            ],
            [
                'user_id' => $this->store->user_id,
                'name' => $productData['name'],
                'description' => $productData['description'] ?? '',
                'sku' => $productData['sku'] ?? null,
                'price' => $productData['price'] ?? 0,
                'stock_quantity' => $productData['stock_quantity'] ?? 0,
                'in_stock' => $productData['in_stock'] ?? true,
                'status' => $productData['status'],
                'permalink' => $productData['permalink'] ?? null,
                'product_url' => $productUrl,
                'raw' => $productData,
            ]
        );
    }

    /**
     * Sync customers from WooCommerce
     */
    public function syncCustomers(): array
    {
        $page = 1;
        $perPage = 100;
        $totalSynced = 0;
        $errors = [];

        do {
            try {
                $response = Http::withBasicAuth($this->auth['username'], $this->auth['password'])
                    ->get($this->baseUrl . 'customers', [
                        'page' => $page,
                        'per_page' => $perPage,
                    ]);

                if (!$response->successful()) {
                    $errors[] = "Failed to fetch customers page {$page}: " . $response->body();
                    break;
                }

                $customers = $response->json();
                
                if (empty($customers)) {
                    break;
                }

                foreach ($customers as $customerData) {
                    $this->syncCustomer($customerData);
                    $totalSynced++;
                }

                $page++;
            } catch (\Exception $e) {
                $errors[] = "Error syncing customers page {$page}: " . $e->getMessage();
                Log::error('WooCommerce customer sync error', [
                    'store_id' => $this->store->id,
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                break;
            }
        } while (count($customers) === $perPage);

        return [
            'synced' => $totalSynced,
            'errors' => $errors
        ];
    }

    /**
     * Sync single customer
     */
    protected function syncCustomer(array $customerData): void
    {
        Customer::updateOrCreate(
            [
                'woo_store_id' => $this->store->id,
                'external_id' => $customerData['id']
            ],
            [
                'user_id' => $this->store->user_id,
                'email' => $customerData['email'],
                'first_name' => $customerData['first_name'] ?? '',
                'last_name' => $customerData['last_name'] ?? '',
                'username' => $customerData['username'] ?? null,
                'role' => $customerData['role'] ?? 'customer',
                'date_created' => isset($customerData['date_created']) ? Carbon::parse($customerData['date_created']) : null,
                'date_modified' => isset($customerData['date_modified']) ? Carbon::parse($customerData['date_modified']) : null,
                'orders_count' => $customerData['orders_count'] ?? 0,
                'total_spent' => $customerData['total_spent'] ?? 0,
                'avatar_url' => $customerData['avatar_url'] ?? null,
                'billing_address' => $customerData['billing'] ?? null,
                'shipping_address' => $customerData['shipping'] ?? null,
                'meta_data' => $customerData['meta_data'] ?? null,
                'raw' => $customerData,
            ]
        );
    }

    /**
     * Sync orders from WooCommerce
     */
    public function syncOrders(): array
    {
        $page = 1;
        $perPage = 100;
        $totalSynced = 0;
        $errors = [];

        do {
            try {
                $response = Http::withBasicAuth($this->auth['username'], $this->auth['password'])
                    ->get($this->baseUrl . 'orders', [
                        'page' => $page,
                        'per_page' => $perPage,
                        'status' => 'any'
                    ]);

                if (!$response->successful()) {
                    $errors[] = "Failed to fetch orders page {$page}: " . $response->body();
                    break;
                }

                $orders = $response->json();
                
                if (empty($orders)) {
                    break;
                }

                foreach ($orders as $orderData) {
                    $this->syncOrder($orderData);
                    $totalSynced++;
                }

                $page++;
            } catch (\Exception $e) {
                $errors[] = "Error syncing orders page {$page}: " . $e->getMessage();
                Log::error('WooCommerce order sync error', [
                    'store_id' => $this->store->id,
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                break;
            }
        } while (count($orders) === $perPage);

        // Update product sales analytics
        $this->updateProductSalesAnalytics();

        return [
            'synced' => $totalSynced,
            'errors' => $errors
        ];
    }

    /**
     * Sync single order
     */
    protected function syncOrder(array $orderData): void
    {
        // Find customer if exists
        $customer = null;
        if (!empty($orderData['customer_id'])) {
            $customer = Customer::where('woo_store_id', $this->store->id)
                ->where('external_id', $orderData['customer_id'])
                ->first();
        }

        Order::updateOrCreate(
            [
                'woo_store_id' => $this->store->id,
                'external_id' => $orderData['id']
            ],
            [
                'user_id' => $this->store->user_id,
                'customer_id' => $customer?->id,
                'order_number' => $orderData['number'] ?? null,
                'status' => $orderData['status'],
                'currency' => $orderData['currency'] ?? 'USD',
                'total' => $orderData['total'] ?? 0,
                'subtotal' => $orderData['subtotal'] ?? 0,
                'tax_total' => $orderData['total_tax'] ?? 0,
                'shipping_total' => $orderData['shipping_total'] ?? 0,
                'discount_total' => $orderData['discount_total'] ?? 0,
                'payment_method' => $orderData['payment_method'] ?? null,
                'payment_method_title' => $orderData['payment_method_title'] ?? null,
                'paid' => in_array($orderData['status'], ['processing', 'completed']),
                'date_created' => isset($orderData['date_created']) ? Carbon::parse($orderData['date_created']) : null,
                'date_modified' => isset($orderData['date_modified']) ? Carbon::parse($orderData['date_modified']) : null,
                'date_completed' => isset($orderData['date_completed']) ? Carbon::parse($orderData['date_completed']) : null,
                'billing_address' => $orderData['billing'] ?? null,
                'shipping_address' => $orderData['shipping'] ?? null,
                'line_items' => $orderData['line_items'] ?? null,
                'customer_note' => $orderData['customer_note'] ?? null,
                'meta_data' => $orderData['meta_data'] ?? null,
                'raw' => $orderData,
            ]
        );
    }

    /**
     * Update product sales analytics based on orders
     */
    protected function updateProductSalesAnalytics(): void
    {
        $orders = Order::where('woo_store_id', $this->store->id)
            ->where('status', 'completed')
            ->get();

        foreach ($orders as $order) {
            if (!empty($order->line_items)) {
                foreach ($order->line_items as $item) {
                    $product = Product::where('woo_store_id', $this->store->id)
                        ->where('external_id', $item['product_id'])
                        ->first();

                    if ($product) {
                        $quantity = $item['quantity'] ?? 1;
                        $total = $item['total'] ?? 0;

                        // Update sales count and revenue
                        $product->total_sales = ($product->total_sales ?? 0) + $quantity;
                        $product->total_revenue = ($product->total_revenue ?? 0) + $total;
                        $product->last_sale_at = $order->date_completed ?? $order->date_created;
                        $product->save();
                    }
                }
            }
        }
    }

    /**
     * Test WooCommerce API connection
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withBasicAuth($this->auth['username'], $this->auth['password'])
                ->get($this->baseUrl . 'system_status');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $response->body(),
                    'status' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
}