<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorShippingProfile;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function edit(Request $request)
    {
        $profile=$request->user()->vendor->shippingProfile;
        return view('dashboard.shipping',compact('profile'));
    }

    public function update(Request $request)
    {
        $data=$request->validate([
            'enabled'=>['required','boolean'],
            'flat_rate'=>['required','numeric','decimal:0,2','min:0','max:999999.99'],
            'free_above'=>['nullable','numeric','decimal:0,2','min:0','max:999999.99'],
            'estimated_days'=>['required','integer','min:1','max:30'],
            'policy_note'=>['nullable','string','max:300'],
        ]);
        VendorShippingProfile::updateOrCreate(
            ['vendor_id'=>$request->user()->vendor->id], $data
        );
        return back()->with('success','Delivery settings saved.');
    }
}
