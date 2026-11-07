<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,processing,shipped,completed,cancelled'],
        ]);

        $orders = Order::query()->with(['vendor', 'user', 'items'])
            ->when($request->filled('q'), fn ($q) => $q->where('number', 'like', '%'.$request->query('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()->paginate(10)->withQueryString();
        return view('dashboard.orders', ['orders' => $orders, 'workspace' => 'admin']);
    }
}
