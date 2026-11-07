<?php

namespace App\Http\Controllers\Admin;

use App\Support\TableQuery;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $states=['pending','processing','shipped','completed','cancelled'];
        $query=TableQuery::apply(Order::query()->with(['vendor','user','items']),$request,['number'],array_combine($states,$states));
        $orders=TableQuery::paginate($query->latest());
        return view('dashboard.orders',['orders'=>$orders,'workspace'=>'admin']);
    }
}
