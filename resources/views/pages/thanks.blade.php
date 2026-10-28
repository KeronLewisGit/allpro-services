<x-layouts.site title="Thank you" :mobile-bar="false">
    <x-page-header photo="scrubber" title="Thank you. We have it." :kicker="$kind" code="OK" lead="Your reference number is below. If you gave an email address, we have sent it to you as well." />
    <section class="wrap mt-12 md:mt-16">
        <div class="max-w-xl">
            {{-- A small "sent" moment: the tick draws itself and a burst of the service colours. Static for reduced motion. --}}
            <div class="relative mb-8 ml-6 h-20 w-20" aria-hidden="true">
                <svg viewBox="0 0 64 64" class="sent-check h-20 w-20"><circle cx="32" cy="32" r="29" /><path d="m19 33 9 9 18-19" /></svg>
                <span class="confetti">
                    @foreach (range(0, 13) as $i)
                        @php($a = $i * (360 / 14))
                        <i style="--c: var(--svc-{{ ['facilities', 'cleaning', 'security', 'industrial', 'washroom', 'consumables', 'landscaping'][$i % 7] }}); --x: {{ round(cos(deg2rad($a)) * (36 + ($i % 3) * 10)) }}px; --y: {{ round(sin(deg2rad($a)) * (36 + ($i % 3) * 10)) }}px; --r: {{ ($i % 2 ? 1 : -1) * (90 + $i * 20) }}deg"></i>
                    @endforeach
                </span>
            </div>
            <x-sitecard :light="true" :cols="2" head="Receipt" :fields="['Reference' => $reference, 'Received' => now()->format('j M Y, H:i'), '*Submission' => $kind, '*Received by' => 'Allpro Services, '.allpro('address.street').', '.allpro('address.town')]" />
            <p class="mt-6 text-ink-2">Quote the reference if you call or email, and we will find your submission straight away.</p>
        </div>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ tel_href(allpro('phone')) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(allpro('phone')) }}</a>
            <a href="{{ route('services') }}" class="btn btn-outline">Our services</a>
            <a href="{{ route('home') }}" class="btn btn-primary">Back to the home page</a>
        </div>
    </section>
</x-layouts.site>
