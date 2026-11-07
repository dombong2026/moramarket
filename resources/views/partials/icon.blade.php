<svg xmlns="http://www.w3.org/2000/svg" width="{{ $size ?? 19 }}" height="{{ $size ?? 19 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="svg-icon">
@switch($name)
@case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>@break
@case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>@break
@case('grid')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
@case('box')<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="M3 7v10l9 5 9-5V7M12 12v10"/>@break
@case('cart')<circle cx="9" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2 3h2l3 13h13l2-10H5"/>@break
@case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
@case('close')<path d="M5 5l14 14M19 5 5 19"/>@break
@case('sun')<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>@break
@case('moon')<path d="M20 15.5A9 9 0 0 1 8.5 4 9 9 0 1 0 20 15.5Z"/>@break
@case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>@break
@case('lock')<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>@break
@case('eye')<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>@break
@case('eye-off')<path d="M3 3 21 21M10.6 5.1A12 12 0 0 1 12 5c6 0 10 7 10 7a17 17 0 0 1-3.1 3.8M6.3 6.3C3.6 8.2 2 12 2 12s4 7 10 7a10 10 0 0 0 4.1-.9"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('edit')<path d="m4 16 12-12 4 4L8 20H4v-4ZM14 6l4 4"/>@break
@case('trash')<path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14M10 11v6M14 11v6"/>@break
@case('arrow')<path d="M5 12h14m-6-6 6 6-6 6"/>@break
@case('globe')<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2c-6 6-6 14 0 20M12 2c6 6 6 14 0 20"/>@break
@case('check')<path d="m4 12 5 5L20 6"/>@break
@case('shield')<path d="M12 2 4 6v6c0 5 4 8 8 10 4-2 8-5 8-10V6l-8-4Z"/><path d="m9 12 2 2 4-4"/>@break
@case('orders')<path d="M5 3h14v18l-3-2-4 2-4-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>@break
@case('tag')<path d="m3 12 9 9 9-9-9-9H3v9Z"/><circle cx="7.5" cy="7.5" r="1"/>@break
@case('store')<path d="M3 9 5 3h14l2 6M3 9v12h18V9M9 21v-7h6v7M2 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/>@break
@case('logout')<path d="M9 3H4v18h5M16 7l5 5-5 5M21 12H9"/>@break
@default<circle cx="12" cy="12" r="9"/>@break
@endswitch
</svg>
