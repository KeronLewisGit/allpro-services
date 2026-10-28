@php
    use App\Enums\Region;
    use App\Enums\RoleType;
    $roleOld = input_string(old('role_type', $selected?->role_type->value ?? $presetRole?->value));
    $jobRoles = $jobs->mapWithKeys(fn ($j) => [$j->id => $j->role_type->value])->all();
@endphp
<x-layouts.site title="Apply" description="Apply to work with Allpro Services as a cleaning operative, security officer or supervisor in Galway, Dublin, Limerick or Cork.">
    <x-page-header :title="$selected ? 'Apply: '.str_replace(' (sample)', '', $selected->title) : 'Apply to work with us'" kicker="Careers" code="C-03" photo="scrubber-school"
        lead="It takes about three minutes and you don't need a CV. Security roles need a current PSA licence." />
    <section class="wrap mt-12 max-w-3xl md:mt-16">
        <div x-data="{ role: @js($roleOld), roles: @js($jobRoles), pick(id) { if (this.roles[id]) this.role = this.roles[id] } }">
        <form method="POST" action="{{ route('careers.store') }}" enctype="multipart/form-data" class="space-y-10" x-data="sendForm" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />
            <x-fieldset n="1" title="The work">
                @if ($jobs->isNotEmpty())
                    <x-select name="job_posting_id" label="Opening" :options="$jobs->mapWithKeys(fn ($j) => [$j->id => $j->title.' · '.$j->region->label()])->all()" :value="$selected?->id" placeholder="General application (no specific opening)" @change="pick($event.target.value)" hint="If you choose an opening, its role and region are used." />
                @endif
                <x-radios name="role_type" label="Role" :value="$roleOld" :options="collect(RoleType::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" :required="true" cols="sm:grid-cols-3" model="role" />
                <x-radios name="region" label="Region you want to work in" :value="$selected?->region->value" :options="Region::options()" :required="true" cols="grid-cols-2 sm:grid-cols-4" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-select name="experience" label="Experience in this kind of work" :required="true" :options="collect(\App\Enums\Experience::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
                    <x-select name="employment_type" label="Hours you want" :optional="true" :options="collect(\App\Enums\EmploymentType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
                </div>
                <div>
                    <p class="label" id="avail-label">Shifts you can work</p>
                    <p class="hint -mt-1 mb-3">Tick every shift you could do. We match you to sites with those hours.</p>
                    <x-availability />
                    <x-error for="availability" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="available_from" label="Earliest start date" type="date" :optional="true" :min="today()->format('Y-m-d')" />
                    <x-select name="drives" label="Do you drive with a full licence?" :optional="true" :options="['1' => 'Yes', '0' => 'No']" />
                </div>
            </x-fieldset>

            <div x-show="role !== 'cleaning_operative'">
                <x-fieldset n="2" title="PSA licence" intro="Security officers must hold a current Private Security Authority licence for the work. For supervisors it is optional.">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="psa_licence_number" label="PSA licence number" inputmode="numeric" maxlength="12" hint="Digits only, as printed on your PSA card." />
                        <x-field name="psa_licence_expires_on" label="Licence expiry date" type="date" :min="today()->addDay()->format('Y-m-d')" />
                    </div>
                    <p class="hint">We check every licence on the PSA's public register before an interview. Skip this section if you are applying for cleaning work.</p>
                </x-fieldset>
            </div>

            <x-fieldset n="3" title="About you">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="first_name" label="First name" :required="true" maxlength="80" autocomplete="given-name" />
                    <x-field name="last_name" label="Last name" :required="true" maxlength="80" autocomplete="family-name" />
                    <x-field name="phone" label="Phone" type="tel" :required="true" maxlength="30" autocomplete="tel" inputmode="tel" hint="We call or text this number." />
                    <x-field name="email" label="Email" type="email" :optional="true" maxlength="255" autocomplete="email" />
                </div>
                <x-select name="county" label="County you live in" :optional="true" :options="array_combine((array) allpro('counties'), (array) allpro('counties'))" />
                <x-radios name="right_to_work" label="Can you work in Ireland?" :options="collect(\App\Enums\RightToWork::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" :required="true" cols="" />
                <p class="hint -mt-2">You will show your documents to HR in person before you start. Don't upload them here.</p>
            </x-fieldset>

            <x-fieldset n="4" title="CV and message">
                <div>
                    <label for="f-cv" class="label">CV <span class="opt">(optional)</span></label>
                    <input id="f-cv" type="file" name="cv" class="input" accept=".pdf,.doc,.docx" aria-describedby="f-cv-hint" @error('cv') aria-invalid="true" @enderror>
                    <span id="f-cv-hint" class="hint">PDF or Word, up to 5 MB.</span>
                    <x-held-upload form="application" field="cv" />
                    <x-error for="cv" />
                </div>
                <x-field name="message" label="Anything else" type="textarea" :optional="true" maxlength="2000" hint="For example, sites you could reach, or work you have done before." />
                <div class="panel flex gap-3 p-4 text-ink-2">
                    <x-icon name="shield" class="mt-0.5 h-5 w-5 flex-none text-primary" />
                    <p><strong class="text-ink">Garda vetting:</strong> if we offer you a role, HR sends you an invitation to the National Vetting Bureau's e-vetting system. Never send vetting forms, ID or passports through this website.</p>
                </div>
                <label class="choice items-start"><input type="checkbox" name="consent" value="1" class="mt-0.5" @checked(old('consent')) required><span>I agree to Allpro Services keeping my application for up to 12 months to consider me for work (<a class="text-link" href="{{ route('privacy') }}">privacy notice</a>).</span></label>
                <x-error for="consent" />
            </x-fieldset>
            <div class="border-t border-line pt-6"><x-submit label="Send my application" /></div>
        </form>
        </div>
    </section>
</x-layouts.site>
