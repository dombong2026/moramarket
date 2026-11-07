<?php

namespace App\Http\Controllers\Admin;

use App\Support\TableQuery;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Services\SafeHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PolicyController extends Controller
{
    public function index(Request $request)
    {
        $query=TableQuery::apply(Policy::query(),$request,['title'],['draft'=>'draft','published'=>'published']);
        $policies=TableQuery::paginate($query->latest());
        return view('dashboard.policies',compact('policies'));
    }

    public function store(Request $request, SafeHtml $safe)
    {
        $data = $this->data($request,$safe);
        $data['editor_id'] = $request->user()->id;
        $data['slug'] = $this->slug($data['title']);
        $data['revision'] = 1;
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        DB::transaction(function () use ($data,$request) {
            $policy = Policy::create($data);
            $policy->revisions()->create([
                'editor_id'=>$request->user()->id,
                'revision'=>1, 'body_html'=>$policy->body_html,
            ]);
        });
        return back()->with('success','Policy created.');
    }

    public function update(Request $request, Policy $policy, SafeHtml $safe)
    {
        $data = $this->data($request,$safe);
        DB::transaction(function () use ($data,$policy,$request) {
            $locked = Policy::whereKey($policy->id)->lockForUpdate()->firstOrFail();
            $data['revision']=$locked->revision+1;
            $data['published_at']=$data['status']==='published' ? ($locked->published_at??now()) : null;
            $data['editor_id']=$request->user()->id;
            $locked->update($data);
            $locked->revisions()->create([
                'editor_id'=>$request->user()->id,
                'revision'=>$locked->revision,
                'body_html'=>$locked->body_html,
            ]);
        });
        return back()->with('success','Policy updated.');
    }

    public function destroy(Policy $policy)
    {
        $policy->delete();
        return back()->with('success','Policy deleted.');
    }

    private function data(Request $request, SafeHtml $safe): array
    {
        $data=$request->validate([
            'title'=>['required','string','min:3','max:190'],
            'body_html'=>['required','string','max:100000'],
            'status'=>['required',Rule::in(['draft','published'])],
        ]);
        $data['body_html']=$safe->clean($data['body_html']);
        if (trim(strip_tags($data['body_html']))==='') {
            throw ValidationException::withMessages(['body_html'=>'The policy must contain readable text.']);
        }
        return $data;
    }

    private function slug(string $title): string
    {
        $base = substr(Str::slug($title) ?: 'policy',0,175);
        $slug=$base;
        while (Policy::where('slug',$slug)->exists()) {
            $slug=substr($base,0,160).'-'.Str::lower(Str::random(8));
        }
        return $slug;
    }
}
