<?php

namespace App\Http\Controllers;

use App\Support\TableQuery;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    private function own(Request $request, SupportTicket $ticket): SupportTicket
    {
        abort_unless($request->user()->isAdmin() || $ticket->user_id === $request->user()->id,404);
        return $ticket;
    }

    public function customerIndex(Request $request)
    {
        $states=['open'=>'open','waiting'=>'waiting','closed'=>'closed'];
        $query=TableQuery::apply(SupportTicket::query()->where('user_id',$request->user()->id),$request,['subject'],$states);
        $tickets=TableQuery::paginate($query->latest());
        return view('dashboard.support-tickets',['tickets'=>$tickets,'workspace'=>'account']);
    }

    public function adminIndex(Request $request)
    {
        $states=['open'=>'open','waiting'=>'waiting','closed'=>'closed'];
        $query=TableQuery::apply(SupportTicket::query()->with('user'),$request,['subject'],$states);
        $tickets=TableQuery::paginate($query->latest());
        return view('dashboard.support-tickets',['tickets'=>$tickets,'workspace'=>'admin']);
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'subject'=>['required','string','min:5','max:190'],
            'message'=>['required','string','min:10','max:10000'],
        ]);
        DB::transaction(function () use ($request,$data) {
            $ticket=SupportTicket::create([
                'user_id'=>$request->user()->id,'subject'=>$data['subject'],'status'=>'open',
            ]);
            $ticket->messages()->create(['user_id'=>$request->user()->id,'body'=>$data['message']]);
        });
        return back()->with('success','Support ticket submitted.');
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        $this->own($request,$ticket);
        return view('dashboard.support-thread',[
            'ticket'=>$ticket->load(['user','messages.author']),
            'workspace'=>$request->user()->isAdmin()?'admin':'account',
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $this->own($request,$ticket);
        $data=$request->validate(['message'=>['required','string','min:2','max:10000']]);

        DB::transaction(function () use ($ticket,$request,$data) {
            $ticket=SupportTicket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            abort_if($ticket->status==='closed',422,'Closed tickets cannot accept replies. Reopen the ticket first.');
            $ticket->messages()->create(['user_id'=>$request->user()->id,'body'=>$data['message']]);
            $ticket->update(['status'=>$request->user()->isAdmin()?'waiting':'open']);
        });
        return back()->with('success','Reply added.');
    }

    public function status(Request $request, SupportTicket $ticket)
    {
        $data=$request->validate(['status'=>['required','in:open,waiting,closed']]);
        $ticket->update(['status'=>$data['status']]);
        return back()->with('success','Ticket status updated.');
    }
}
