@props(['fields' => [], 'head' => 'Site instructions', 'ref' => null, 'light' => false, 'cols' => 2])
{{--
    A site instruction card, like the laminated sheet in a guard hut. Keys starting with "*"
    span the full width.
--}}
<dl {{ $attributes->class(['sitecard', 'light' => $light])->merge(['style' => '--cols: '.$cols]) }}>
    <div class="sc-head"><span>{{ $head }}</span>@if ($ref)<span class="num">{{ $ref }}</span>@endif</div>
    @foreach ($fields as $label => $value)
        <div @class(['wide' => str_starts_with($label, '*')])>
            <dt>{{ ltrim($label, '*') }}</dt>
            <dd>{{ $value }}</dd>
        </div>
    @endforeach
</dl>
