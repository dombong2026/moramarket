<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Fresh Produce', 'Grocery & Food', 'Fashion & Clothing', 'Electronics',
            'Home & Living', 'Beauty & Personal Care', 'Books & Education',
            'Health & Wellness', 'Sports & Outdoors', 'Handmade & Crafts',
            'Digital Products', 'Professional Services',
        ] as $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }
    }
}
