<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,processing,shipped,completed,cancelled'],
        ]);

        $orders = $request->user()->vendor->orders()->with(['user', 'items'])
            ->when($request->filled('q'), fn ($q) => $q->where('number', 'like', '%'.$request->query('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()->paginate(10)->withQueryString();
        return view('dashboard.orders', ['orders' => $orders, 'workspace' => 'vendor']);
    }

    public function update(Request $request, Order $order)
    {
        abort_unless($order->vendor_id === $request->user()->vendor->id, 404);
        $data = $request->validate(['status' => ['required', 'in:processing,shipped,completed,cancelled']]);

        DB::transaction(function () use ($order, $data) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (in_array($order->status, ['cancelled', 'completed'], true)) {
                throw ValidationException::withMessages(['status' => 'A final order cannot be changed.']);
            }
            if ($data['status'] === 'cancelled') {
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                    }
                }
            }
            $order->update(['status' => $data['status']]);
        });

        return back()->with('success', 'Order status updated.');
    }
}
