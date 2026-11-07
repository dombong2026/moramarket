<?php

namespace App\Http\Controllers\Admin;

use App\Support\TableQuery;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query=TableQuery::apply(Category::query()->withCount('products'),$request,['name'],['active'=>true,'inactive'=>false],'is_active');
        $categories=TableQuery::paginate($query->orderBy('name'));
        return view('dashboard.categories',compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCategory($request);
        Category::create($data);
        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateCategory($request, $category);
        $category->update($data);
        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Move or delete category products before removing the category.']);
        }
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['slug'] = Str::slug($data['name']);
        $request->validate([
            'name' => [Rule::unique('categories', 'name')->ignore($category?->id)],
        ]);
        if (Category::where('slug', $data['slug'])->when($category, fn ($q) => $q->whereKeyNot($category->id))->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Choose a different category name.']);
        }
        return $data;
    }
}
