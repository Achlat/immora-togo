@props(['name', 'class' => 'size-[18px]'])
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
    @case('home')<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>@break
    @case('building')<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 7h2M14 7h2M8 11h2M14 11h2M8 15h2M14 15h2"/><path d="M10 21v-3h4v3"/>@break
    @case('key')<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M17 6l2 2M15 8l2 2"/>@break
    @case('users')<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>@break
    @case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>@break
    @case('crown')<path d="M3 8l4.5 4L12 5l4.5 7L21 8l-2 11H5z"/>@break
    @case('file')<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>@break
    @case('receipt')<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 8h6M9 12h6"/>@break
    @case('cash')<rect x="2.5" y="6" width="19" height="12" rx="1.5"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9.5v5M18 9.5v5"/>@break
    @case('wallet')<path d="M4 7h15a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/><path d="M4 7l11-3v3"/><path d="M16 13.5h.01"/>@break
    @case('vault')<rect x="3" y="4" width="18" height="16" rx="1.5"/><circle cx="12" cy="12" r="3.5"/><path d="M12 8.5v1M12 14.5v1M8.5 12h1M14.5 12h1M6 20v1.5M18 20v1.5"/>@break
    @case('chart')<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>@break
    @case('tool')<path d="M14.5 6.5a4 4 0 0 0-5.3 5.3L3.5 17.5l3 3 5.7-5.7a4 4 0 0 0 5.3-5.3l-2.5 2.5-2.5-.5-.5-2.5z"/>@break
    @case('statement')<path d="M6 3h12v18H6z"/><path d="M9 7h6M9 11h6M9 15h3"/><path d="M15 15l1.5 1.5L19 14"/>@break
    @case('settings')<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.1a1.6 1.6 0 0 0-1-1.5 1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.1a1.6 1.6 0 0 0 1.5-1 1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3h0a1.6 1.6 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.1a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8v0a1.6 1.6 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/>@break
    @case('shield')<path d="M12 3 4.5 6v6c0 4.5 3.2 7.8 7.5 9 4.3-1.2 7.5-4.5 7.5-9V6z"/><path d="m9 12 2 2 4-4"/>@break
    @case('team')<circle cx="12" cy="7" r="3"/><circle cx="5" cy="10" r="2.2"/><circle cx="19" cy="10" r="2.2"/><path d="M7 20a5 5 0 0 1 10 0M1.5 19a3.5 3.5 0 0 1 5-3.2M22.5 19a3.5 3.5 0 0 0-5-3.2"/>@break
    @case('logout')<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 16l-4-4 4-4M6 12h10"/>@break
    @case('search')<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>@break
    @case('plus')<path d="M12 5v14M5 12h14"/>@break
    @case('arrow-right')<path d="M5 12h14M13 6l6 6-6 6"/>@break
    @case('arrow-left')<path d="M19 12H5M11 6l-6 6 6 6"/>@break
    @case('check')<path d="m5 12.5 4.5 4.5L19 7.5"/>@break
    @case('x')<path d="M6 6l12 12M18 6 6 18"/>@break
    @case('alert')<path d="M12 4 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/>@break
    @case('info')<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>@break
    @case('phone')<rect x="6" y="2.5" width="12" height="19" rx="2"/><path d="M11 18.5h2"/>@break
    @case('download')<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>@break
    @case('send')<path d="M21 3 10 14M21 3l-7 18-4-7-7-4z"/>@break
    @case('eye')<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>@break
    @case('lock')<rect x="4.5" y="10.5" width="15" height="10" rx="1.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>@break
    @case('calendar')<rect x="3.5" y="5" width="17" height="15.5" rx="1.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>@break
    @case('pin')<path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>@break
    @case('menu')<path d="M4 7h16M4 12h16M4 17h16"/>@break
    @case('swap')<path d="M7 4 3 8l4 4M3 8h14M17 20l4-4-4-4M21 16H7"/>@break
    @default<circle cx="12" cy="12" r="8"/>
@endswitch
</svg>
