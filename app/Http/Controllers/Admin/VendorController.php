<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
        ]);
        $vendors = Vendor::with('user')->withCount('products')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->query('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()->paginate(10)->withQueryString();
        return view('dashboard.vendors', compact('vendors'));
    }

    public function changeStatus(Request $request, Vendor $vendor)
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);

        DB::transaction(function () use ($vendor, $data) {
            $vendor->update(['status' => $data['status']]);
            $vendor->user()->update(['role' => $data['status'] === 'approved' ? 'vendor' : 'customer']);
        });

        return back()->with('success', 'Vendor status updated.');
    }
}
