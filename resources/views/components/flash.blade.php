@foreach (['success' => ['ok', 'check'], 'status' => ['neutral', 'info'], 'error' => ['alert', 'alert'], 'demo' => ['gold', 'info']] as $key => [$tone, $icon])
    @if (session($key))
        @php
            $classes = [
                'ok' => 'border-ok/25 bg-ok-wash text-ok',
                'neutral' => 'border-line bg-white text-ink',
                'alert' => 'border-alert/25 bg-alert-wash text-alert',
                'gold' => 'border-gold/40 bg-gold-wash text-gold-deep',
            ][$tone];
        @endphp
        <div x-data="{ show: true }" x-show="show" role="{{ $tone === 'alert' ? 'alert' : 'status' }}"
            class="mb-6 flex items-start gap-3 rounded-[10px] border px-4 py-3 text-[13.5px] font-medium {{ $classes }}">
            <x-icon :name="$icon" class="mt-0.5 size-4 shrink-0" />
            <p class="flex-1">{{ session($key) }}</p>
            <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Fermer">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    @endif
@endforeach
