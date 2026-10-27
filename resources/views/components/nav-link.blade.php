@props(['route', 'icon', 'match' => null])
@php($active = request()->routeIs(...(array) ($match ?? $route)))
<a href="{{ route($route) }}" class="nav-item" @if ($active) aria-current="page" @endif>
    <x-icon :name="$icon" class="size-[17px] shrink-0" />
    <span class="truncate">{{ $slot }}</span>
</a>
