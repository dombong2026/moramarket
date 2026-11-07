@extends('layouts.workspace')
@section('title','Vendor Management')
@section('subtitle','Review seller applications and manage marketplace access.')
@section('page_actions')<a href="{{ route('admin.home') }}" class="btn btn-outline">Overview</a>@endsection
@section('content')
<div class="table-panel">
@include('partials.table-tools',['statuses'=>['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected']])
<div class="table-scroll"><table class="data-table"><thead><tr><th>No</th><th>Store</th><th>Owner</th><th>Products</th><th>Status</th><th>Applied</th><th>Actions</th></tr></thead><tbody>
@forelse($vendors as $vendor)<tr><td>{{ $vendors->firstItem()+$loop->index }}</td><td><strong>{{ $vendor->name }}</strong></td><td>{{ $vendor->user->name }}<span class="small">{{ $vendor->user->email }}</span></td><td>{{ $vendor->products_count }}</td><td><span class="pill {{ $vendor->status }}">{{ $vendor->status }}</span></td><td>{{ $vendor->created_at->format('Y-m-d') }}</td><td><x-ui.action-trigger menu="vendor-menu-{{ $vendor->id }}" label="Vendor actions" /></td></tr>
@empty<tr><td colspan="7" style="padding:35px;text-align:center" class="muted">No matching vendor applications.</td></tr>@endforelse
</tbody></table></div>
@include('partials.table-footer',['paginator'=>$vendors])
</div>
@foreach($vendors as $vendor)
<x-ui.action-menu id="vendor-menu-{{ $vendor->id }}"><button type="button" data-open-modal="vendor-view-{{ $vendor->id }}">View details</button>@if($vendor->status!=='approved')<button type="button" data-open-modal="vendor-approve-{{ $vendor->id }}">Approve vendor</button>@endif @if($vendor->status!=='rejected')<button class="danger" type="button" data-open-modal="vendor-reject-{{ $vendor->id }}">Reject / suspend</button>@endif</x-ui.action-menu>
<x-ui.modal-frame id="vendor-view-{{ $vendor->id }}"><div class="modal-header"><h2>Vendor details</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div><div class="modal-body"><p><strong>Store:</strong> {{ $vendor->name }}</p><p><strong>Owner:</strong> {{ $vendor->user->name }}</p><p><strong>Email:</strong> {{ $vendor->user->email }}</p><p><strong>Status:</strong> {{ ucfirst($vendor->status) }}</p><p style="white-space:pre-wrap;margin-top:14px">{{ $vendor->description }}</p></div><div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>Close</button></div></x-ui.modal-frame>
@foreach(['approved'=>'approve','rejected'=>'reject'] as $state=>$verb)
<x-ui.modal-frame id="vendor-{{ $verb }}-{{ $vendor->id }}"><div class="modal-header"><h2>{{ ucfirst($verb) }} vendor?</h2><button type="button" class="modal-close" data-close-modal>@include('partials.icon',['name'=>'close'])</button></div><form action="{{ route('admin.vendors.status',$vendor) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $state }}"><div class="modal-body"><p>{{ ucfirst($verb) }} <strong>{{ $vendor->name }}</strong>? {{ $state==='approved' ? 'Approved vendors may publish products.' : 'Rejected vendors lose seller dashboard access and their listings stop being publicly visible.' }}</p></div><div class="modal-footer"><button type="button" class="btn btn-outline" data-close-modal>Cancel</button><button type="submit" class="btn {{ $state==='approved'?'btn-primary':'btn-danger' }}">Confirm {{ ucfirst($verb) }}</button></div></form></x-ui.modal-frame>
@endforeach
@endforeach
@endsection
