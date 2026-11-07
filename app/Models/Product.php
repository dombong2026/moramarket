<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'vendor_id', 'category_id', 'title', 'slug', 'description',
        'type', 'price', 'stock', 'image_path', 'status',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'stock' => 'integer'];
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereHas('vendor', fn (Builder $vendors) => $vendors->where('status', 'approved'))
            ->whereHas('category', fn (Builder $categories) => $categories->where('is_active', true));
    }

    public function isPurchasable(): bool
    {
        return $this->type === 'physical'
            && $this->status === 'published'
            && $this->stock > 0
            && $this->vendor?->status === 'approved'
            && $this->category?->is_active;
    }
}
