@props(['menu', 'label' => 'Actions'])
<button type="button" class="action-trigger" data-menu-id="{{ $menu }}"
    aria-label="{{ $label }}" aria-haspopup="menu" aria-controls="{{ $menu }}" aria-expanded="false">
    <span aria-hidden="true">⋮</span>
</button>
