@props(['status', 'label' => null])
@php
    $tones = [
        'paid' => 'ok', 'active' => 'ok', 'occupied' => 'ok', 'validated' => 'ok', 'confirmed' => 'ok', 'accepted' => 'ok',
        'overdue' => 'alert', 'reversed' => 'alert', 'locked' => 'alert', 'disabled' => 'alert', 'failed' => 'alert', 'suspended' => 'alert',
        'partial' => 'warn', 'notice' => 'warn', 'draft' => 'warn', 'trial' => 'warn', 'reserved' => 'warn', 'pending' => 'warn', 'works' => 'warn',
    ];
    $tone = $tones[$status] ?? 'neutral';
    $classes = [
        'ok' => 'bg-ok-wash text-ok', 'alert' => 'bg-alert-wash text-alert',
        'warn' => 'bg-warn-wash text-warn', 'neutral' => 'bg-ivory text-graphite',
    ][$tone];
    $dot = ['ok' => 'bg-ok', 'alert' => 'bg-alert', 'warn' => 'bg-warn', 'neutral' => 'bg-faint'][$tone];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-[3px] text-[11.5px] font-bold whitespace-nowrap {$classes}"]) }}>
    <span class="size-1.5 rounded-full {{ $dot }}"></span>{{ $label ?? $status }}
</span>
