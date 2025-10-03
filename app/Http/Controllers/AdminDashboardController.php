<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use App\Models\WooStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        // Add middleware to ensure only admins can access
        $this->middleware(function ($request, $next) {
            if (!auth()->user() || !auth()->user()->is_admin) {
                abort(403, 'Unauthorized access');
            }
            return $next($request);
        });
    }
    
    public function index()
    {
        // Overall system statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::whereHas('wooStores')->count(),
            'total_stores' => WooStore::count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_customers' => Customer::count(),
            'total_revenue' => Order::where('status', 'completed')->sum('total'),
            'average_products_per_user' => Product::count() / max(User::count(), 1),
        ];
        
        // Recent user activity
        $recentUsers = User::with('wooStores')
            ->withCount(['products', 'orders', 'customers'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Top performing users by revenue
        $topUsers = User::withSum(['orders' => function($query) {
                $query->where('status', 'completed');
            }], 'total')
            ->withCount(['products', 'orders'])
            ->orderBy('orders_sum_total', 'desc')
            ->limit(10)
            ->get();
        
        // System health metrics (SQLite compatible)
        $healthMetrics = [
            'stores_synced_today' => WooStore::whereDate('last_synced_at', today())->count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'revenue_today' => Order::where('status', 'completed')->whereDate('created_at', today())->sum('total'),
            'new_users_this_month' => User::whereRaw("strftime('%Y-%m', created_at) = ?", [now()->format('Y-m')])->count(),
        ];
        
        return view('admin.dashboard', compact('stats', 'recentUsers', 'topUsers', 'healthMetrics'));
    }
    
    public function users(Request $request)
    {
        $query = User::with('wooStores')
            ->withCount(['products', 'orders', 'customers'])
            ->withSum(['orders' => function($q) {
                $q->where('status', 'completed');
            }], 'total');
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        
        // Filter by user type
        if ($request->has('user_type') && $request->user_type) {
            if ($request->user_type === 'active') {
                $query->whereHas('wooStores');
            } elseif ($request->user_type === 'inactive') {
                $query->whereDoesntHave('wooStores');
            }
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['created_at', 'name', 'email', 'products_count', 'orders_count', 'orders_sum_total'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        $users = $query->paginate(20);
        
        return view('admin.users', compact('users'));
    }
    
    public function userDetails($userId)
    {
        $user = User::with(['wooStores', 'products', 'orders', 'customers'])
            ->withCount(['products', 'orders', 'customers'])
            ->withSum(['orders' => function($q) {
                $q->where('status', 'completed');
            }], 'total')
            ->findOrFail($userId);
        
        // User's recent activity
        $recentOrders = Order::where('user_id', $userId)
            ->with(['customer', 'wooStore'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $recentProducts = Product::where('user_id', $userId)
            ->with('wooStore')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Performance metrics
        $metrics = [
            'total_revenue' => Order::where('user_id', $userId)->where('status', 'completed')->sum('total'),
            'average_order_value' => Order::where('user_id', $userId)->where('status', 'completed')->avg('total'),
            'total_inquiries' => Product::where('user_id', $userId)->sum('total_inquiries'),
            'total_sales' => Product::where('user_id', $userId)->sum('total_sales'),
            'conversion_rate' => $this->calculateConversionRate($userId),
        ];
        
        return view('admin.user-details', compact('user', 'recentOrders', 'recentProducts', 'metrics'));
    }
    
    public function stores()
    {
        $stores = WooStore::with('user')
            ->withCount(['products', 'orders', 'customers'])
            ->withSum(['orders' => function($q) {
                $q->where('status', 'completed');
            }], 'total')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.stores', compact('stores'));
    }
    
    public function analytics()
    {
        // Revenue analytics (SQLite compatible)
        $monthlyRevenue = Order::where('status', 'completed')
            ->selectRaw("strftime('%m', created_at) as month, strftime('%Y', created_at) as year, SUM(total) as revenue, COUNT(*) as orders")
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
        
        // User growth (SQLite compatible)
        $userGrowth = User::selectRaw("strftime('%m', created_at) as month, strftime('%Y', created_at) as year, COUNT(*) as users")
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
        
        // Product distribution by category/store
        $productDistribution = WooStore::withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(10)
            ->get();
        
        // Top performing products across all users
        $topProducts = Product::with(['user', 'wooStore'])
            ->orderBy('total_sales', 'desc')
            ->limit(20)
            ->get();
        
        return view('admin.analytics', compact(
            'monthlyRevenue', 
            'userGrowth', 
            'productDistribution', 
            'topProducts'
        ));
    }
    
    private function calculateConversionRate($userId)
    {
        $totalInquiries = Product::where('user_id', $userId)->sum('total_inquiries');
        $totalSales = Product::where('user_id', $userId)->sum('total_sales');
        
        if ($totalInquiries == 0) {
            return 0;
        }
        
        return round(($totalSales / $totalInquiries) * 100, 2);
    }
}
