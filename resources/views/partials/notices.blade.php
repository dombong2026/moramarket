@if(session('success') || session('status') || $errors->any())
<div class="toast-stack" role="status" aria-live="polite">
    @if(session('success'))
    <div class="toast toast-success" role="status">
        @include('partials.icon',['name'=>'check'])
        <span>{{ session('success') }}</span>
        <button type="button" data-dismiss-toast aria-label="Dismiss">@include('partials.icon',['name'=>'close','size'=>16])</button>
    </div>
    @endif
    @if(session('status'))
    <div class="toast" role="status">
        @include('partials.icon',['name'=>'mail'])
        <span>{{ session('status') }}</span>
        <button type="button" data-dismiss-toast aria-label="Dismiss">@include('partials.icon',['name'=>'close','size'=>16])</button>
    </div>
    @endif
    @if($errors->any())
    <div class="toast toast-error" role="alert">
        <span>{{ $errors->first() }}</span>
        <button type="button" data-dismiss-toast aria-label="Dismiss">@include('partials.icon',['name'=>'close','size'=>16])</button>
    </div>
    @endif
</div>
@endif
<span id="mm-old-modal" data-modal="{{ old('_modal','') }}" hidden></span>
@include('partials.confirm-dialog')
@include('partials.link-dialog')
