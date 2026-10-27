@props(['amount', 'size' => null])
<span {{ $attributes->merge(['class' => 'num whitespace-nowrap']) }}>{{ number_format((int) $amount, 0, ',', "\u{202F}") }}<span class="ml-1 text-[0.78em] font-semibold text-muted">FCFA</span></span>
