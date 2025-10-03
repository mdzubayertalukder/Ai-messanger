<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use App\Models\WooStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get user's WooCommerce stores
        $stores = WooStore::where('user_id', $user->id)->get();
        
        // Get products with analytics
        $products = Product::where('user_id', $user->id)
            ->with('wooStore')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Dashboard statistics
        $stats = [
            'total_products' => Product::where('user_id', $user->id)->count(),
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'total_customers' => Customer::where('user_id', $user->id)->count(),
            'total_revenue' => Order::where('user_id', $user->id)
                ->where('status', 'completed')
                ->sum('total'),
            'total_inquiries' => Product::where('user_id', $user->id)->sum('total_inquiries'),
            'total_sales' => Product::where('user_id', $user->id)->sum('total_sales'),
            'stores_count' => $stores->count(),
        ];
        
        return view('user.dashboard', compact('products', 'stats', 'stores'));
    }
    
    public function products(Request $request)
    {
        $user = Auth::user();
        
        $query = Product::where('user_id', $user->id)->with('wooStore');
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // Filter by store
        if ($request->has('store_id') && $request->store_id) {
            $query->where('woo_store_id', $request->store_id);
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['name', 'price', 'stock_quantity', 'total_inquiries', 'total_sales', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        $products = $query->paginate(20);
        $stores = WooStore::where('user_id', $user->id)->get();
        
        return view('dashboard.products', compact('products', 'stores'));
    }
    
    public function orders(Request $request)
    {
        $user = Auth::user();
        
        $query = Order::where('user_id', $user->id)->with(['customer', 'wooStore']);
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('customer', function($customerQuery) use ($request) {
                      $customerQuery->where('name', 'like', '%' . $request->search . '%')
                                   ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
            });
        }
        
        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        // Filter by store
        if ($request->has('store_id') && $request->store_id) {
            $query->where('woo_store_id', $request->store_id);
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['order_number', 'total', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        $orders = $query->paginate(20);
        $stores = WooStore::where('user_id', $user->id)->get();
        
        // Order statistics
        $stats = [
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'pending_orders' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
            'completed_orders' => Order::where('user_id', $user->id)->where('status', 'completed')->count(),
            'cancelled_orders' => Order::where('user_id', $user->id)->where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('user_id', $user->id)->where('status', 'completed')->sum('total'),
        ];
        
        return view('user.orders', compact('orders', 'stores', 'stats'));
    }
    
    public function analytics()
    {
        $user = Auth::user();
        
        // Product performance analytics
        $topProducts = Product::where('user_id', $user->id)
            ->orderBy('total_sales', 'desc')
            ->limit(10)
            ->get();
        
        $mostInquired = Product::where('user_id', $user->id)
            ->orderBy('total_inquiries', 'desc')
            ->limit(10)
            ->get();
        
        // Revenue analytics (SQLite compatible)
        $monthlyRevenue = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw("strftime('%m', created_at) as month, strftime('%Y', created_at) as year, SUM(total) as revenue")
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
        
        // Store performance
        $storeStats = WooStore::where('user_id', $user->id)
            ->withCount(['products', 'orders', 'customers'])
            ->withSum('orders', 'total')
            ->get();
        
        return view('dashboard.analytics', compact(
            'topProducts', 
            'mostInquired', 
            'monthlyRevenue', 
            'storeStats'
        ));
    }
}
