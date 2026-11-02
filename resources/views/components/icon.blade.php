@props(['name', 'class' => 'icon'])
@php
    // Same stroke icon set as ICONS in public/assets/js/main.js
    $paths = [
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1z"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c1-3.5 3.6-5.5 7-5.5s6 2 7 5.5M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5c2 .8 3.4 2.6 4 5.5"/>',
        'home' => '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>',
        'car' => '<path d="M5 16V11l2-5h10l2 5v5M3 16h18v3H3zM7 19v1M17 19v1M5 11h14"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>',
        'shield' => '<path d="M12 3 4.5 6v6c0 4.6 3.2 8.2 7.5 9.5 4.3-1.3 7.5-4.9 7.5-9.5V6z"/>',
        'coin' => '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5c-.5-1-1.5-1.5-2.5-1.5-1.5 0-2.5.8-2.5 2s1 1.7 2.5 2 2.5.8 2.5 2-1 2-2.5 2c-1 0-2-.5-2.5-1.5M12 6.5V8M12 16v1.5"/>',
        'palm' => '<path d="M12 21V11M12 11c-2-3-6-3.5-8-2 3 0 5 1 8 2zm0 0c2-3 6-3.5 8-2-3 0-5 1-8 2zm0 0c-1-3.5-4-5.5-6.5-5 2.5 1 4.5 2.5 6.5 5zm0 0c1-3.5 4-5.5 6.5-5-2.5 1-4.5 2.5-6.5 5zM7 21h10"/>',
        'grid' => '<rect x="4" y="4" width="6.5" height="6.5"/><rect x="13.5" y="4" width="6.5" height="6.5"/><rect x="4" y="13.5" width="6.5" height="6.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5"/>',
        'doc' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/>',
        'wallet' => '<path d="M3 7h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM3 7l13-3v3"/><circle cx="16.5" cy="13.5" r="1.2"/>',
        'card' => '<rect x="2.5" y="5" width="19" height="14" rx="1.5"/><path d="M2.5 10h19M6 15h4"/>',
        'cal' => '<rect x="3" y="5" width="18" height="16" rx="1"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'gift' => '<rect x="3" y="8" width="18" height="4"/><path d="M5 12v9h14v-9M12 8v13M12 8S10.5 3 8 3.5 7 8 12 8zm0 0s1.5-5 4-4.5S17 8 12 8z"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    ];
@endphp
<svg class="{{ $class }}" viewBox="0 0 24 24" aria-hidden="true">{!! $paths[$name] ?? '' !!}</svg>
