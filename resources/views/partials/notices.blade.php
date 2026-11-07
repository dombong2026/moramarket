@if(session('success') || session('status') || $errors->any())
<div class="toast-stack" role="status" aria-live="polite">
    @if(session('success'))<div class="toast toast-success">@include('partials.icon',['name'=>'check']) <span>{{ session('success') }}</span><button type="button" data-dismiss-toast aria-label="Dismiss">@include('partials.icon',['name'=>'close','size'=>16])</button></div>@endif
    @if(session('status'))<div class="toast">@include('partials.icon',['name'=>'mail']) <span>{{ session('status') }}</span><button type="button" data-dismiss-toast aria-label="Dismiss">@include('partials.icon',['name'=>'close','size'=>16])</button></div>@endif
    @if($errors->any())<div class="toast toast-error"><span>{{ $errors->first() }}</span><button type="button" data-dismiss-toast aria-label="Dismiss">@include('partials.icon',['name'=>'close','size'=>16])</button></div>@endif
</div>
@endif
