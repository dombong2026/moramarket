@props(['id', 'label' => 'Actions'])
<div id="{{ $id }}" class="action-popover" popover="auto" role="menu" aria-label="{{ $label }}">
  {{ $slot }}
</div>
