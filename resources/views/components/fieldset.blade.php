@props(['n', 'title', 'intro' => null])
{{-- A numbered form section. id/data-step let <x-form-steps> follow and tick it off. --}}
<fieldset {{ $attributes->merge(['class' => 'scroll-mt-28 space-y-5 border-t border-line pt-6', 'id' => 'step-'.$n, 'data-step' => $n]) }}>
    <legend class="fieldset-title float-left mb-5 w-full"><x-checkpoint :n="$n" :light="true" />{{ $title }}</legend>
    @if ($intro)<p class="clear-both -mt-2 text-ink-2">{{ $intro }}</p>@endif
    <div class="clear-both space-y-5">{{ $slot }}</div>
</fieldset>
