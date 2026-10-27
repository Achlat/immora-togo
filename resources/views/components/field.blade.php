@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'options' => null,
    'money' => false,
    'required' => false,
    'placeholder' => null,
])
@php
    $id = 'f_'.str_replace(['[', ']', '.'], '_', $name);
    $current = old($name, $value);
    $error = $errors->first($name);
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-gold-deep"> *</span>@endif</label>

    @if ($options !== null)
        <select id="{{ $id }}" name="{{ $name }}" class="input" @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_err" @endif
            {{ $attributes->except('class') }} @required($required)>
            @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="3" class="input" placeholder="{{ $placeholder }}"
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_err" @endif {{ $attributes->except('class') }}>{{ $current }}</textarea>
    @elseif ($money)
        <div class="relative" x-data="moneyInput(@js((string) $current))">
            <input id="{{ $id }}" name="{{ $name }}" type="text" inputmode="numeric" autocomplete="off"
                class="input num pr-16" x-model="display" @input="format($event)" placeholder="{{ $placeholder ?? '0' }}"
                @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_err" @endif {{ $attributes->except('class') }} @required($required)>
            <span class="pointer-events-none absolute inset-y-0 right-3.5 flex items-center text-[12px] font-bold text-muted">FCFA</span>
        </div>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : $current }}"
            class="input" placeholder="{{ $placeholder }}"
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_err" @endif {{ $attributes->except('class') }} @required($required)>
    @endif

    @if ($error)
        <p id="{{ $id }}_err" class="error" role="alert">{{ $error }}</p>
    @elseif ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
