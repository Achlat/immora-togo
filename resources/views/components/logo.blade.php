@props(['tone' => 'dark', 'size' => 'md'])
@php
    $word = $tone === 'light' ? 'text-white' : 'text-ink';
    $box = $size === 'lg' ? 'size-10' : 'size-8';
    $text = $size === 'lg' ? 'text-[26px]' : 'text-[21px]';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg class="{{ $box }}" viewBox="0 0 40 40" fill="none" aria-hidden="true">
        <rect x="0.5" y="0.5" width="39" height="39" rx="9" class="{{ $tone === 'light' ? 'fill-ink-2 stroke-ink-3' : 'fill-ink stroke-ink' }}"/>
        <path d="M12 30V18.5a8 8 0 0 1 16 0V30" stroke="#D8BD7E" stroke-width="1.6"/>
        <path d="M20 30V15" stroke="#D8BD7E" stroke-width="1.6"/>
        <path d="M9 30h22" stroke="#D8BD7E" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
    <span class="display {{ $text }} leading-none {{ $word }}" style="font-weight:520">Immora</span>
</span>
