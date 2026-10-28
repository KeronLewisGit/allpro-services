@props(['n', 'light' => false])
{{-- A numbered patrol checkpoint tag: used for steps and numbered points. --}}
<span {{ $attributes->class(['checkpoint', 'light' => $light]) }} aria-hidden="true">{{ str_pad((string) $n, 2, '0', STR_PAD_LEFT) }}</span>
