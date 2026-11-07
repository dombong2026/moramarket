<?php

namespace App\Http\Controllers\Admin;

use App\Support\TableQuery;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query=TableQuery::apply(Vendor::query()->with('user')->withCount('products'),$request,['name'],['pending'=>'pending','approved'=>'approved','rejected'=>'rejected']);
        $vendors=TableQuery::paginate($query->latest());
        return view('dashboard.vendors',compact('vendors'));
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
