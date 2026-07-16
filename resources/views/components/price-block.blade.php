<div {{ $attributes->class('price-block') }}>
    @if ($label)
        <span class="price-block__label">{{ $label }}</span>
    @endif

    <span class="price-block__value">{{ $formattedAmount }}</span>

    @if ($suffix)
        <span class="price-block__suffix">{{ $suffix }}</span>
    @endif
</div>
