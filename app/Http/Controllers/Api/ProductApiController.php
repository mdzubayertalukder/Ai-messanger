<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Models\WooStore;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProductApiController extends Controller
{
    /**
     * Get all products for a specific user
     */
    public function getUserProducts(Request $request, $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $query = Product::where('user_id', $userId)->with(['wooStore']);
            
            // Apply filters
            if ($request->has('store_id') && $request->store_id) {
                $query->where('woo_store_id', $request->store_id);
            }
            
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('search') && $request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('sku', 'like', '%' . $request->search . '%');
                });
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSorts = [
                'created_at', 'name', 'price', 'stock_quantity', 
                'total_inquiries', 'total_sales', 'total_revenue'
            ];
            
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }
            
            $perPage = min($request->get('per_page', 15), 100); // Max 100 items per page
            $products = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get specific product details
     */
    public function getProduct($productId): JsonResponse
    {
        try {
            $product = Product::with(['user', 'wooStore'])->findOrFail($productId);
            
            return response()->json([
                'success' => true,
                'data' => $product
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Get user's product analytics summary
     */
    public function getUserProductAnalytics($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $analytics = [
                'total_products' => Product::where('user_id', $userId)->count(),
                'total_inquiries' => Product::where('user_id', $userId)->sum('total_inquiries'),
                'total_sales' => Product::where('user_id', $userId)->sum('total_sales'),
                'total_revenue' => Product::where('user_id', $userId)->sum('total_revenue'),
                'average_price' => Product::where('user_id', $userId)->avg('price'),
                'products_in_stock' => Product::where('user_id', $userId)->where('stock_quantity', '>', 0)->count(),
                'products_out_of_stock' => Product::where('user_id', $userId)->where('stock_quantity', '<=', 0)->count(),
            ];
            
            // Top performing products
            $topProducts = Product::where('user_id', $userId)
                ->with('wooStore')
                ->orderBy('total_sales', 'desc')
                ->limit(5)
                ->get();
            
            // Recent activity
            $recentProducts = Product::where('user_id', $userId)
                ->with('wooStore')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'analytics' => $analytics,
                    'top_products' => $topProducts,
                    'recent_products' => $recentProducts,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update product inquiry count
     */
    public function incrementInquiry(Request $request, $productId): JsonResponse
    {
        try {
            $request->validate([
                'count' => 'integer|min:1|max:100'
            ]);
            
            $product = Product::findOrFail($productId);
            $count = $request->get('count', 1);
            
            $product->increment('total_inquiries', $count);
            $product->update(['last_inquiry_at' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Inquiry count updated successfully',
                'data' => [
                    'product_id' => $product->id,
                    'total_inquiries' => $product->total_inquiries,
                    'last_inquiry_at' => $product->last_inquiry_at
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating inquiry count',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update product sale count
     */
    public function incrementSale(Request $request, $productId): JsonResponse
    {
        try {
            $request->validate([
                'count' => 'integer|min:1|max:100',
                'amount' => 'numeric|min:0'
            ]);
            
            $product = Product::findOrFail($productId);
            $count = $request->get('count', 1);
            $amount = $request->get('amount', $product->price * $count);
            
            $product->increment('total_sales', $count);
            $product->increment('total_revenue', $amount);
            $product->update(['last_sale_at' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sale count updated successfully',
                'data' => [
                    'product_id' => $product->id,
                    'total_sales' => $product->total_sales,
                    'total_revenue' => $product->total_revenue,
                    'last_sale_at' => $product->last_sale_at
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating sale count',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get products by store
     */
    public function getStoreProducts($storeId): JsonResponse
    {
        try {
            $store = WooStore::with('user')->findOrFail($storeId);
            
            $products = Product::where('woo_store_id', $storeId)
                ->with('wooStore')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'store' => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'url' => $store->url,
                    'user' => $store->user
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Search products across all users (admin only)
     */
    public function searchProducts(Request $request): JsonResponse
    {
        try {
            // Check if user is admin (you might want to add proper middleware)
            if (!auth()->user() || !auth()->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }
            
            $query = Product::with(['user', 'wooStore']);
            
            if ($request->has('search') && $request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('sku', 'like', '%' . $request->search . '%')
                      ->orWhereHas('user', function($userQuery) use ($request) {
                          $userQuery->where('name', 'like', '%' . $request->search . '%')
                                   ->orWhere('email', 'like', '%' . $request->search . '%');
                      });
                });
            }
            
            $products = $query->paginate(20);
            
            return response()->json([
                'success' => true,
                'data' => $products
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
