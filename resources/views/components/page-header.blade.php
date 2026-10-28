@props(['title', 'lead' => null, 'kicker' => null, 'code' => null, 'photo' => null, 'position' => 'center', 'card' => null, 'cardHead' => null, 'svc' => null])
{{--
    The one hero band every inner page uses, so titles start at the same height and left edge
    site-wide. After allpro.ie's own bands: a grey photograph washed in a brand colour (blue by
    default, or a service colour via svc), the short title bar over an uppercase title, and a
    slanted cut into the page below. An info card sits on the right on wide screens.
    Breadcrumbs, actions and chips go in the slot. The home hero is the "hero" variant.
--}}
@php
    $fields = $card ?? [
        'Office' => 'Oranmore HQ',
        'Section' => $kicker ?? $title,
        '*Helpdesk' => format_phone((string) allpro('phone')),
        '*Cover' => 'Galway · Dublin · Limerick · Cork',
    ];
@endphp
<section {{ $attributes->merge(['class' => 'page-header tinted relative isolate overflow-hidden bg-deep-900 text-white on-dark']) }} @if($svc) data-svc="{{ $svc }}" @endif>
    @if ($photo)
        <div class="hero-photo absolute inset-0 -z-10">
            <x-photo :key="$photo" alt="" :eager="true" sizes="100vw" class="h-full w-full" :position="$position" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
    @endif
    <div class="wrap grid gap-8 pb-16 pt-10 md:pb-24 md:pt-16 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-end lg:gap-12">
        <div class="min-w-0">
            @if ($kicker)<x-lanyard :code="$code">{{ $kicker }}</x-lanyard>@endif
            <h1 class="display-2 mt-5 max-w-4xl">{{ $title }}</h1>
            @if ($lead)<p class="lead mt-4 text-white md:mt-5">{{ $lead }}</p>@endif
            {{ $slot }}
        </div>
        <x-sitecard :fields="$fields" :head="$cardHead ?? 'Allpro Services'" class="hidden lg:grid" />
    </div>
    @if ($photo && ! in_array($photo, (array) allpro('own_photos'), true))
        <span class="photo-note" style="bottom: auto; top: 0.75rem">Illustrative photo</span>
    @endif
    <div class="cut" aria-hidden="true"></div>
</section>
