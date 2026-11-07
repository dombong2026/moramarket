<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use Illuminate\Http\Request;

class KnowledgePublicController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q'=>['nullable','string','max:100'],
            'category'=>['nullable','string','max:180'],
        ]);
        $categories=KnowledgeCategory::orderBy('name')->get();
        $articles=KnowledgeArticle::query()->public()->with('category')
            ->when($request->filled('q'),fn ($q)=>$q->where('title','like','%'.$request->q.'%'))
            ->when($request->filled('category'),fn ($q)=>$q->whereHas('category',fn ($c)=>$c->where('slug',$request->category)))
            ->latest('published_at')->paginate(10)->withQueryString();
        return view('storefront.knowledge',compact('articles','categories'));
    }

    public function show(KnowledgeArticle $article)
    {
        abort_unless(KnowledgeArticle::query()->public()->whereKey($article->id)->exists(),404);
        return view('storefront.knowledge-show',['article'=>$article->load('category')]);
    }
}
