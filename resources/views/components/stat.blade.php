@props(['label', 'value', 'unit' => null, 'note' => null, 'tone' => 'default'])
<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <p class="eyebrow">{{ $label }}</p>
    <p class="mt-3 flex items-baseline gap-1.5">
        <span class="display num text-[30px] leading-none {{ $tone === 'alert' ? 'text-alert' : 'text-ink' }}">{{ $value }}</span>
        @if ($unit)<span class="text-[12px] font-bold text-muted">{{ $unit }}</span>@endif
    </p>
    @if ($note)<p class="mt-2.5 text-[12.5px] text-muted">{!! $note !!}</p>@endif
    {{ $slot }}
</div>
