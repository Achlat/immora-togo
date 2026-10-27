@props(['placeholder' => 'Rechercher…', 'filters' => null])
<form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-3">
    <div class="relative min-w-[220px] flex-1">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-faint" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="input h-9 pl-9 text-[13.5px]">
    </div>
    {{ $slot }}
    <button class="btn btn-ghost btn-sm">Filtrer</button>
</form>
