<?php

namespace App\Http\Controllers\Vendor;

use App\Support\TableQuery;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\MarketplaceNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $states=['pending','processing','shipped','completed','cancelled'];
        $query=TableQuery::apply(Order::query()->where('vendor_id',$request->user()->vendor->id)->with(['user','items']),$request,['number'],array_combine($states,$states));
        $orders=TableQuery::paginate($query->latest());
        return view('dashboard.orders',['orders'=>$orders,'workspace'=>'vendor']);
    }

    public function update(Request $request, Order $order, MarketplaceNotificationService $notices)
    {
        abort_unless($order->vendor_id===$request->user()->vendor->id,404);
        $data=$request->validate([
            'status'=>['required','in:processing,shipped,completed,cancelled'],
            'shipping_carrier'=>['nullable','string','max:100'],
            'tracking_number'=>['nullable','string','max:120'],
        ]);

        DB::transaction(function () use ($order,$data,$notices) {
            $locked=Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $transitions=[
                'pending'=>['processing','cancelled'],
                'processing'=>['shipped','cancelled'],
                'shipped'=>['completed'],
                'completed'=>[],
                'cancelled'=>[],
            ];
            if (!in_array($data['status'],$transitions[$locked->status]??[],true)) {
                throw ValidationException::withMessages(['status'=>'Invalid order transition. Advance each delivery stage in order.']);
            }
            $updates=['status'=>$data['status']];
            if ($data['status']==='cancelled') {
                foreach ($locked->items as $item) {
                    if ($item->product_id) {
                        Product::whereKey($item->product_id)->increment('stock',$item->quantity);
                    }
                }
            }
            if ($data['status']==='shipped') {
                $updates['shipped_at']=now();
                $updates['shipping_carrier']=$data['shipping_carrier']??null;
                $updates['tracking_number']=$data['tracking_number']??null;
            }
            if ($data['status']==='completed') {
                $updates['delivered_at']=now();
            }
            $locked->update($updates);
            $notices->orderUpdated($locked);
        });
        return back()->with('success','Delivery status updated.');
    }
}
