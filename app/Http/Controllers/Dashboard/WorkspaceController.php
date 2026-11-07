<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function admin()
    {
        return view('dashboard.overview', [
            'workspace' => 'admin',
            'metrics' => [
                'Orders' => Order::count(),
                'Vendors' => Vendor::where('status', 'approved')->count(),
                'Pending vendors' => Vendor::where('status', 'pending')->count(),
                'Products' => Product::count(),
                'Categories' => Category::count(),
                'Customers' => User::where('role', 'customer')->count(),
            ],
            'recentOrders' => Order::with(['user', 'vendor'])->latest()->limit(5)->get(),
        ]);
    }

    public function vendor(Request $request)
    {
        $vendor = $request->user()->vendor;
        return view('dashboard.overview', [
            'workspace' => 'vendor',
            'metrics' => [
                'Published products' => $vendor->products()->where('status', 'published')->count(),
                'Draft products' => $vendor->products()->where('status', 'draft')->count(),
                'Orders' => $vendor->orders()->count(),
                'Pending orders' => $vendor->orders()->where('status', 'pending')->count(),
            ],
            'recentOrders' => $vendor->orders()->with('user')->latest()->limit(5)->get(),
        ]);
    }
}
