@props(['icon' => 'file', 'title', 'text' => null])
<div class="flex flex-col items-center px-6 py-16 text-center">
    <span class="mb-4 flex size-12 items-center justify-center rounded-full border border-line bg-paper text-gold-deep">
        <x-icon :name="$icon" class="size-5" />
    </span>
    <p class="display text-[19px] text-ink">{{ $title }}</p>
    @if ($text)<p class="mt-1.5 max-w-sm text-[13.5px] text-muted">{{ $text }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
