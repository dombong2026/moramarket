<?php

namespace App\Http\Controllers;

use App\Models\Policy;

class PolicyPublicController extends Controller
{
    public function index()
    {
        $policies=Policy::query()->public()->orderBy('title')->paginate(10);
        return view('storefront.policies',compact('policies'));
    }

    public function show(Policy $policy)
    {
        abort_unless(Policy::query()->public()->whereKey($policy->id)->exists(),404);
        return view('storefront.policy-show', compact('policy'));
    }
}
