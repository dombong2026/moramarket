<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Services\SafeHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KnowledgeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q'=>['nullable','string','max:100'],
            'status'=>['nullable','in:draft,published'],
        ]);
        $articles=KnowledgeArticle::query()->with('category')
            ->when($request->filled('q'),fn ($q)=>$q->where('title','like','%'.$request->q.'%'))
            ->when($request->filled('status'),fn ($q)=>$q->where('status',$request->status))
            ->latest()->paginate(10)->withQueryString();
        $categories=KnowledgeCategory::query()->withCount('articles')->orderBy('name')->get();
        return view('dashboard.knowledge',compact('articles','categories'));
    }

    public function storeCategory(Request $request)
    {
        $data=$request->validate(['name'=>['required','string','min:2','max:160']]);
        $slug=Str::slug($data['name']);
        if (KnowledgeCategory::where('slug',$slug)->exists()) {
            throw ValidationException::withMessages(['name'=>'This category already exists.']);
        }
        KnowledgeCategory::create(['name'=>$data['name'],'slug'=>$slug]);
        return back()->with('success','Knowledge category created.');
    }

    public function updateCategory(Request $request, KnowledgeCategory $category)
    {
        $data=$request->validate(['name'=>['required','string','min:2','max:160']]);
        $slug=Str::slug($data['name']);
        if (KnowledgeCategory::where('slug',$slug)->whereKeyNot($category->id)->exists()) {
            throw ValidationException::withMessages(['name'=>'This category name is already in use.']);
        }
        $category->update(['name'=>$data['name'],'slug'=>$slug]);
        return back()->with('success','Knowledge category updated.');
    }

    public function deleteCategory(KnowledgeCategory $category)
    {
        if ($category->articles()->exists()) {
            throw ValidationException::withMessages(['category'=>'Move or remove its articles before deletion.']);
        }
        $category->delete();
        return back()->with('success','Knowledge category deleted.');
    }

    public function store(Request $request, SafeHtml $safe)
    {
        $data=$this->validateArticle($request,$safe);
        $data['author_id']=$request->user()->id;
        $data['slug']=$this->slug($data['title']);
        $data['published_at']=$data['status']==='published' ? now() : null;
        KnowledgeArticle::create($data);
        return back()->with('success','Knowledge article created.');
    }

    public function update(Request $request, KnowledgeArticle $article, SafeHtml $safe)
    {
        $data=$this->validateArticle($request,$safe);
        $data['published_at']=$data['status']==='published' ? ($article->published_at??now()) : null;
        $article->update($data);
        return back()->with('success','Knowledge article updated.');
    }

    public function destroy(KnowledgeArticle $article)
    {
        $article->delete();
        return back()->with('success','Knowledge article deleted.');
    }

    private function validateArticle(Request $request, SafeHtml $safe): array
    {
        $data=$request->validate([
            'title'=>['required','string','min:3','max:190'],
            'category_id'=>['required',Rule::exists('knowledge_categories','id')],
            'body_html'=>['required','string','max:100000'],
            'status'=>['required','in:draft,published'],
        ]);
        $data['body_html']=$safe->clean($data['body_html']);
        if (trim(strip_tags($data['body_html']))==='') {
            throw ValidationException::withMessages(['body_html'=>'Article text is required.']);
        }
        return $data;
    }

    private function slug(string $title): string
    {
        $base=substr(Str::slug($title)?:'article',0,175);
        $slug=$base;
        while (KnowledgeArticle::where('slug',$slug)->exists()) {
            $slug=substr($base,0,160).'-'.Str::lower(Str::random(8));
        }
        return $slug;
    }
}
