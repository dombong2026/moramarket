<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'number', 'user_id', 'vendor_id', 'status', 'payment_method',
        'payment_status', 'customer_name', 'customer_email', 'shipping_address', 'total',
    ];

    protected function casts(): array
    {
        return ['total' => 'decimal:2'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
