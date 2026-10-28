@props(['code' => null])
{{-- Section kicker drawn as an ID badge on a lanyard; $code goes in the tab (region or service code). --}}
<p {{ $attributes->merge(['class' => 'lanyard']) }}><span>{{ $slot }}</span>@if ($code)<b><i>{{ $code }}</i></b>@endif</p>
