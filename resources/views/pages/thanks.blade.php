<x-layouts.site title="Thank you" :mobile-bar="false">
    <x-page-header photo="scrubber" title="Thank you. We have it." :kicker="$kind" code="OK" lead="Your reference number is below. If you gave an email address, we have sent it to you as well." />
    <section class="wrap mt-12 md:mt-16">
        <div class="max-w-xl">
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
