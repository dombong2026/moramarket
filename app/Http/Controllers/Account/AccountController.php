<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function home(Request $request)
    {
        $user = $request->user()->load('vendor');
        $ordersCount = $user->orders()->count();
        $recentOrders = $user->orders()->with('vendor')->latest()->limit(5)->get();
        return view('dashboard.account', compact('user', 'ordersCount', 'recentOrders'));
    }

    public function orders(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,processing,shipped,completed,cancelled'],
        ]);
        $orders = Order::where('user_id', $request->user()->id)->with(['vendor', 'items'])
            ->when($request->filled('q'), fn ($q) => $q->where('number', 'like', '%'.$request->query('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()->paginate(10)->withQueryString();
        return view('dashboard.orders', ['orders' => $orders, 'workspace' => 'account']);
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'min:3', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        if ($user->role === 'admin' || $user->vendor()->exists()) {
            return back()->withErrors(['store_name' => 'An application already exists or you are an administrator.']);
        }

        Vendor::create([
            'user_id' => $user->id,
            'name' => $data['store_name'],
            'slug' => Str::slug($data['store_name']).'-'.Str::lower(Str::random(5)),
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);
        return back()->with('success', 'Vendor application submitted for review.');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $user->name = $data['name'];
        if ($user->email !== $data['email']) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
        }
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return back()->with('success', 'Profile updated.');
    }
}
