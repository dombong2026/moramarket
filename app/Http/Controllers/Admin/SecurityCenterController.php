<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Support\TableQuery;
use Illuminate\Http\Request;

class SecurityCenterController extends Controller
{
    public function index(Request $request)
    {
        $query=TableQuery::apply(
            AuditEvent::query()->with('actor'),
            $request,
            ['subject_type'],
            [
                'created'=>'created',
                'updated'=>'updated',
                'deleted'=>'deleted',
                'signed_in'=>'signed_in',
                'signed_out'=>'signed_out',
            ],
            'action'
        );
        $events=TableQuery::paginate($query->latest('id'));

        return view('dashboard.audit',compact('events'));
    }
}
