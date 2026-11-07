<nav class="workspace-nav" aria-label="{{ ucfirst($scope) }} dashboard navigation">
@foreach(\App\Support\WorkspaceNavigation::for($scope, request()) as $nav)
  <a href="{{ $nav['url'] }}" @class(['active' => $nav['active']])
    @if($nav['active']) aria-current="page" @endif>
    @include('partials.icon', ['name'=>$nav['icon'], 'size'=>18])
    <span>{{ $nav['label'] }}</span>
  </a>
@endforeach
</nav>
