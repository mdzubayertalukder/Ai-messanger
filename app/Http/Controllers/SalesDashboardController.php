<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\WooStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Sales overview statistics
        $stats = [
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'completed_orders' => Order::where('user_id', $user->id)->where('status', 'completed')->count(),
            'pending_orders' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
            'total_revenue' => Order::where('user_id', $user->id)->where('status', 'completed')->sum('total'),
            'average_order_value' => Order::where('user_id', $user->id)->where('status', 'completed')->avg('total'),
            'total_customers' => Customer::where('user_id', $user->id)->count(),
            'repeat_customers' => Customer::where('user_id', $user->id)->where('orders_count', '>', 1)->count(),
        ];
        
        // Recent orders
        $recentOrders = Order::where('user_id', $user->id)
            ->with(['customer', 'wooStore'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Monthly sales data for chart (SQLite compatible)
        $monthlySales = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw("strftime('%m', created_at) as month, strftime('%Y', created_at) as year, COUNT(*) as orders, SUM(total) as revenue")
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
        
        // Top selling products
        $topProducts = Product::where('user_id', $user->id)
            ->orderBy('total_sales', 'desc')
            ->limit(5)
            ->get();
        
        return view('sales.dashboard', compact('stats', 'recentOrders', 'monthlySales', 'topProducts'));
    }
    
    public function orders(Request $request)
    {
        $user = Auth::user();
        
        $query = Order::where('user_id', $user->id)->with(['customer', 'wooStore']);
        
        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        // Filter by store
        if ($request->has('store_id') && $request->store_id) {
            $query->where('woo_store_id', $request->store_id);
        }
        
        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Search by order number or customer email
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('customer', function($customerQuery) use ($request) {
                      $customerQuery->where('email', 'like', '%' . $request->search . '%');
                  });
            });
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['created_at', 'order_number', 'status', 'total'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        $orders = $query->paginate(20);
        $stores = WooStore::where('user_id', $user->id)->get();
        
        return view('dashboard.orders', compact('orders', 'stores'));
    }
    
    public function customers(Request $request)
    {
        $user = Auth::user();
        
        $query = Customer::where('user_id', $user->id)->with('wooStore');
        
        // Search by name or email
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('email', 'like', '%' . $request->search . '%')
                  ->orWhere('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%');
            });
        }
        
        // Filter by store
        if ($request->has('store_id') && $request->store_id) {
            $query->where('woo_store_id', $request->store_id);
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['created_at', 'first_name', 'last_name', 'email', 'orders_count', 'total_spent'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        $customers = $query->paginate(20);
        $stores = WooStore::where('user_id', $user->id)->get();
        
        return view('dashboard.customers', compact('customers', 'stores'));
    }
    
    public function analytics()
    {
        $user = Auth::user();
        
        // Revenue analytics by time periods (SQLite compatible)
        $dailyRevenue = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->selectRaw("strftime('%Y-%m-%d', created_at) as date, SUM(total) as revenue, COUNT(*) as orders")
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
        
        $monthlyRevenue = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw("strftime('%m', created_at) as month, strftime('%Y', created_at) as year, SUM(total) as revenue, COUNT(*) as orders")
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
        
        // Product performance
        $productPerformance = Product::where('user_id', $user->id)
            ->selectRaw('*, (total_sales * price) as revenue')
            ->orderBy('total_sales', 'desc')
            ->limit(10)
            ->get();
        
        // Customer analytics
        $customerAnalytics = [
            'top_customers' => Customer::where('user_id', $user->id)
                ->orderBy('total_spent', 'desc')
                ->limit(10)
                ->get(),
            'customer_segments' => [
                'new' => Customer::where('user_id', $user->id)->where('orders_count', 1)->count(),
                'returning' => Customer::where('user_id', $user->id)->where('orders_count', '>', 1)->count(),
                'vip' => Customer::where('user_id', $user->id)->where('total_spent', '>', 1000)->count(),
            ]
        ];
        
        // Store comparison
        $storeComparison = WooStore::where('user_id', $user->id)
            ->withCount(['orders', 'customers', 'products'])
            ->withSum('orders', 'total')
            ->get();
        
        return view('dashboard.sales-analytics', compact(
            'dailyRevenue', 
            'monthlyRevenue', 
            'productPerformance', 
            'customerAnalytics', 
            'storeComparison'
        ));
    }
}
