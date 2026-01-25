@props([
    'name' => false,
    'label' => false,
    'count' => 0,
    'icon' => false,
    'icon_style' => false,
    'tooltip' => false,
])
<!-- start tab nav item -->
<li {{ $attributes->merge(['class' => '']) }}>
    <a href="#{{ $name ?? 'info' }}" data-toggle="tab"{!! ($tooltip) ? ' data-tooltip="true" title="'.$tooltip.'"' : '' !!}>

        @if ($icon)
            <i class="{{ $icon }}" style="font-size: 17px" aria-hidden="true"></i>
        @endif

        <span class="sr-only">
            {{ $label }}
        </span>

        @if ($count > 0)
            <span class="badge">{{ number_format($count) }} </span>
        @endif

    </a>
</li>
<!-- end tab nav item -->