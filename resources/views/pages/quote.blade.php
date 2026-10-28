@php
    use App\Models\QuoteRequest;
    $services = (array) allpro('services');
    $chosen = old('services', $preset ? [$preset] : []);
@endphp
<x-layouts.site title="Request a quote" description="Request a quote and a site survey from Allpro Services for cleaning, security, facilities management, washroom services, grounds or pest control.">
    <x-page-header title="Request a quote and site survey" kicker="New sites" code="Q-01" photo="pressure-wash"
        lead="Tell us about the site and choose a weekday for a survey. The request goes to the sales owner for the county the site is in, and you get a reference number straight away." />
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_19rem]">
        <form method="POST" action="{{ route('quote.store') }}" enctype="multipart/form-data" class="min-w-0 space-y-10" x-data="sendForm" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />
            <x-fieldset n="1" title="Services">
                <x-checks name="services" label="Which services do you need?" :options="collect($services)->mapWithKeys(fn ($s, $k) => [$k => $s['title']])->all()" cols="sm:grid-cols-2" :selected="$chosen" />
                <x-select name="sector" label="Sector" :required="true" :options="(array) allpro('sectors')" />
                <x-field name="cover" label="What cover do you need?" type="textarea" :optional="true" maxlength="2000" rows="3" hint="For example: daily office clean after 6 pm; a static guard 24/7; monthly washroom service." />
            </x-fieldset>

            <x-fieldset n="2" title="The site">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="site_name" label="Site name" :required="true" maxlength="160" hint="For example: head office, Block B, the retail park." />
                    <x-field name="site_count" label="Number of sites" type="number" :required="true" min="1" max="500" :value="1" inputmode="numeric" />
                </div>
                <x-field name="site_address" label="Street address" :required="true" maxlength="255" autocomplete="address-line1" />
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-field name="town" label="Town or city" :required="true" maxlength="80" autocomplete="address-level2" />
                    <x-select name="county" label="County" :required="true" :options="array_combine((array) allpro('counties'), (array) allpro('counties'))" />
                    <x-field name="eircode" label="Eircode" :optional="true" maxlength="8" autocomplete="postal-code" class="uppercase" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="floor_area_m2" label="Approximate floor area (m²)" type="number" :optional="true" min="10" inputmode="numeric" />
                    <x-field name="start_on" label="When would the service start?" type="date" :optional="true" :min="today()->format('Y-m-d')" />
                </div>
                <div>
                    <label class="choice"><input type="checkbox" name="is_tender" value="1" @checked(old('is_tender')) x-data x-ref="tender" @change="$dispatch('tender', $event.target.checked)"><span>This is a public or private tender</span></label>
                </div>
                <div class="grid gap-5 sm:grid-cols-2" x-data="{ on: @js((bool) old('is_tender')) }" @tender.window="on = $event.detail" x-show="on">
                    <x-field name="tender_reference" label="Tender reference" :optional="true" maxlength="80" hint="For example the eTenders RFT number." />
                    <x-field name="tender_closes_on" label="Tender closing date" type="date" :min="today()->format('Y-m-d')" />
                </div>
                <div>
                    <label for="f-files" class="label">Floor plans or specification <span class="opt">(optional)</span></label>
                    <input id="f-files" type="file" name="files[]" multiple class="input" accept=".pdf,.doc,.docx,.xls,.xlsx,.dwg,.jpg,.jpeg,.png" aria-describedby="f-files-hint" @error('files') aria-invalid="true" @enderror @error('files.*') aria-invalid="true" @enderror>
                    <span id="f-files-hint" class="hint">Up to three files, 10 MB each: PDF, Word, Excel, DWG, JPG or PNG.</span>
                    <x-held-upload form="quote" field="files" />
                    <x-error for="files" /><x-error for="files.*" />
                </div>
            </x-fieldset>

            <x-fieldset n="3" title="Site survey" intro="Surveys take place Monday to Friday. Pick the day that suits you; the regional team confirms by phone or email.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="survey_on" label="Preferred survey date" type="date" :required="true" :min="QuoteRequest::earliestSurvey()->format('Y-m-d')" :max="today()->addMonths(3)->subDay()->format('Y-m-d')" hint="At least {{ QuoteRequest::SURVEY_LEAD_DAYS }} working days from today." />
                    <x-select name="survey_window" label="Time of day" :required="true" :options="QuoteRequest::WINDOWS" value="any" />
                </div>
            </x-fieldset>

            <x-fieldset n="4" title="Your details">
                <x-field name="organisation" label="Organisation" :required="true" maxlength="160" autocomplete="organization" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="contact_name" label="Your name" :required="true" maxlength="120" autocomplete="name" />
                    <x-field name="contact_role" label="Your role" :optional="true" maxlength="120" autocomplete="organization-title" />
                    <x-field name="email" label="Email" type="email" :required="true" maxlength="255" autocomplete="email" />
                    <x-field name="phone" label="Phone" type="tel" :required="true" maxlength="30" autocomplete="tel" inputmode="tel" />
                </div>
                <x-field name="message" label="Anything else" type="textarea" :optional="true" maxlength="3000" rows="4" hint="Access arrangements, parking, a contact on site, or the current contract end date." />
                <label class="choice items-start"><input type="checkbox" name="consent" value="1" class="mt-0.5" @checked(old('consent')) required><span>I agree to Allpro Services using these details to arrange the survey and prepare a quote (<a class="text-link" href="{{ route('privacy') }}">privacy notice</a>).</span></label>
                <x-error for="consent" />
            </x-fieldset>
            <div class="border-t border-line pt-6"><x-submit label="Send the request" /></div>
        </form>
        <aside class="space-y-6">
            <x-sitecard :light="true" :cols="1" head="What happens next" :fields="['1. Today' => 'Reference number by email', '2. Next' => 'Your regional sales owner confirms the survey', '3. Survey' => 'Walk the site together', '4. After' => 'A costed proposal']" />
            <div class="panel p-5">
                <p class="font-semibold">Already a client?</p>
                <p class="mt-1 text-ink-2">Log requests and complaints on your site's service-desk link.</p>
                <a href="{{ route('clients') }}" class="btn btn-outline btn-sm mt-4">Client service desk</a>
            </div>
            <div class="panel p-5">
                <p class="font-semibold">Rather talk it through?</p>
                <a href="{{ tel_href(allpro('phone')) }}" class="mt-2 inline-flex min-h-11 items-center font-display text-xl font-bold text-link">{{ format_phone(allpro('phone')) }}</a>
            </div>
        </aside>
    </section>
</x-layouts.site>
