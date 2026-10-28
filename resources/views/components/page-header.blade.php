@props(['title', 'lead' => null, 'kicker' => null, 'code' => null, 'photo' => null, 'position' => 'center', 'card' => null, 'cardHead' => null])
{{--
    The one hero band every inner page uses, so titles start at the same height and left edge
    site-wide. Deep night-shift band with a site-plan patrol route (or a photo behind a scrim),
    a lanyard-badge kicker, and a site instruction card on the right. A hi-vis band closes it.
    Breadcrumbs, actions and chips go in the slot. The home hero is the "hero" variant.
--}}
@php
    $fields = $card ?? [
        'Office' => 'Oranmore HQ',
        'Helpdesk' => format_phone((string) allpro('phone')),
        '*Section' => $kicker ?? $title,
        '*Cover' => 'Galway · Dublin · Limerick · Cork',
    ];
@endphp
<section {{ $attributes->merge(['class' => 'siteplan relative isolate overflow-hidden bg-deep-900 text-white on-dark']) }}>
    @if ($photo)
        <div class="hero-photo absolute inset-0 -z-10">
            <x-photo :key="$photo" alt="" :eager="true" sizes="100vw" class="h-full w-full" :position="$position" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
    @endif
    <div class="wrap grid gap-8 pb-10 pt-10 md:pb-14 md:pt-16 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-end lg:gap-12">
        <div class="min-w-0">
            @if ($kicker)<x-lanyard :code="$code">{{ $kicker }}</x-lanyard>@endif
            <h1 class="display-2 mt-5 max-w-4xl">{{ $title }}</h1>
            @if ($lead)<p class="lead mt-4 text-white/85 md:mt-5">{{ $lead }}</p>@endif
            {{ $slot }}
        </div>
        <x-sitecard :fields="$fields" :head="$cardHead ?? 'Site instructions'" class="hidden lg:grid" />
    </div>
    @if ($photo && ! in_array($photo, (array) allpro('own_photos'), true))
        <span class="photo-note" style="bottom: 1.75rem">Illustrative photo</span>
    @endif
    <div class="hivis" aria-hidden="true"></div>
</section>
