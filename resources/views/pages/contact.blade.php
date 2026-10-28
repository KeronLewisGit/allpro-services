<x-layouts.site title="Contact" description="Contact Allpro Services: head office at 12A Westlink Business Park, Oranmore, Co. Galway, with offices in Dublin, Limerick and Cork. Telephone, email and an enquiry form.">
    <x-page-header title="Contact Allpro" kicker="Contact" code="K-01" photo="oranmore" position="center 60%"
        lead="For a quote, use the quote form so the request reaches the sales team for your region. Existing clients can log requests on their site's service-desk link. For anything else, call, email or send a message below." />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="space-y-8">
            <div>
                <p class="kicker text-ink-3">Helpdesk</p>
                <a href="{{ tel_href(allpro('phone')) }}" class="mt-1 inline-flex min-h-11 items-center font-display text-2xl font-bold text-link">{{ format_phone(allpro('phone')) }}</a>
                <p class="hint">From outside Ireland, dial +353 91 381 111.</p>
            </div>
            <div>
                <p class="kicker text-ink-3">Email</p>
                <a href="mailto:{{ allpro('email') }}" class="mt-1 inline-flex min-h-11 items-center text-lg font-semibold text-link">{{ allpro('email') }}</a>
            </div>
            <div>
                <p class="kicker text-ink-3">Head office</p>
                <p class="mt-1 text-lg">{{ allpro('short_name') }}<br>{{ allpro('address.street') }}<br>{{ allpro('address.town') }}, {{ allpro('address.county') }}</p>
                <a href="{{ allpro('map_url') }}" target="_blank" rel="noopener" class="mt-1 inline-flex min-h-11 items-center gap-1.5 font-semibold text-link">Get directions<x-icon name="external" class="h-4 w-4" /><span class="sr-only">(opens Google Maps)</span></a>
            </div>
            <div>
                <p class="kicker text-ink-3">Regional offices</p>
                <ul class="mt-2 space-y-1.5">
                    @foreach ((array) allpro('regions') as $r)
                        <li class="flex justify-between gap-4 border-b border-line pb-1.5"><span>{{ $r['label'] }}</span><span class="text-right text-ink-2">{{ $r['office'] }}</span></li>
                    @endforeach
                </ul>
                <p class="hint">Contact every office through the helpdesk number above.</p>
            </div>
            <div class="panel space-y-3 p-5">
                <p class="font-semibold">Looking for a quote?</p>
                <a href="{{ route('quote.create') }}" class="btn btn-primary w-full">Request a quote</a>
                <a href="{{ route('clients') }}" class="btn btn-outline w-full">Existing client</a>
            </div>
        </aside>

        <form method="POST" action="{{ route('contact.store') }}" class="min-w-0 space-y-8" x-data="sendForm" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />
            <x-fieldset n="1" title="Send a message">
                <x-radios name="topic" label="What is it about?" :options="collect(\App\Enums\EnquiryTopic::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" :required="true" cols="sm:grid-cols-2" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="name" label="Your name" :required="true" maxlength="120" autocomplete="name" />
                    <x-field name="organisation" label="Organisation" :optional="true" maxlength="160" autocomplete="organization" />
                    <x-field name="email" label="Email" type="email" :required="true" maxlength="255" autocomplete="email" />
                    <x-field name="phone" label="Phone" type="tel" :optional="true" maxlength="30" autocomplete="tel" inputmode="tel" />
                </div>
                <x-field name="message" label="Message" type="textarea" :required="true" maxlength="3000" rows="6" />
            </x-fieldset>
            <div class="border-t border-line pt-6">
                <x-submit label="Send message" />
                <p class="hint mt-3">The form works without JavaScript. We keep these details only to reply. <a class="text-link" href="{{ route('privacy') }}">Privacy notice</a>.</p>
            </div>
        </form>
    </section>
</x-layouts.site>
