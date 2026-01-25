@props([
    'box_style' => 'default',
    'header' => false,
    'footer' => false,
])

<!-- Start box component -->
<div class="box box-{{ $box_style }}">

    @if ($header)
    <x-box.header>
        {{ $header }}
    </x-box.header>
    @endif

    <div class="box-body">
        {{ $slot }}
    </div>

    @if ($footer)
        <x-box.footer>
        {{ $footer }}
        </x-box.footer>
    @endif
</div>