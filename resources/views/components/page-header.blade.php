@props(['title', 'kicker' => null, 'back' => null])
<header class="mb-8 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-3 inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-muted hover:text-ink">
                <x-icon name="arrow-left" class="size-3.5" /> Retour
            </a>
        @endif
        @if ($kicker)
            <p class="eyebrow mb-2 !text-gold-deep">{{ $kicker }}</p>
        @endif
        <h1 class="display text-[32px] leading-[1.1] text-ink md:text-[38px]">{{ $title }}</h1>
        @isset($subtitle)
            <p class="mt-2 max-w-2xl text-[14.5px] text-muted">{{ $subtitle }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</header>
