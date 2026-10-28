@php
    $ld = ['@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $job->title, 'description' => $job->summary,
        'datePosted' => $job->created_at->toDateString(), 'employmentType' => $job->employment_type->schema(),
        'hiringOrganization' => ['@type' => 'Organization', 'name' => allpro('name'), 'sameAs' => route('home')],
        'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $job->location, 'addressRegion' => $job->region->label(), 'addressCountry' => 'IE']]]
        + ($job->closes_on ? ['validThrough' => $job->closes_on->endOfDay()->toIso8601String()] : []);
@endphp
<x-layouts.site :title="$job->title" :description="$job->summary" :json-ld="[$ld]">
    <x-page-header :title="$job->title" :kicker="$job->role_type->label()" :code="$job->region->code()" :photo="$job->role_type->needsPsa() ? 'guard-dusk' : 'scrubber-school'" :lead="$job->summary"
        card-head="Job card" :card="['Region' => $job->region->label(), 'Openings' => (string) $job->openings, '*Hours' => $job->hours ?: $job->employment_type->label(), '*Closes' => $job->closes_on?->format('j F Y') ?? 'Open until filled']">
        <div class="mt-6 flex flex-wrap gap-3"><a href="{{ route('careers.apply', ['job' => $job->slug]) }}" class="btn btn-hivis">Apply for this job<x-icon name="arrow-right" class="nudge h-4 w-4" /></a><a href="{{ route('careers.index') }}" class="btn btn-on-dark">All openings</a></div>
    </x-page-header>
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-2">
        @if ($job->dutyList())
            <div><x-lanyard code="01">The work</x-lanyard><ul class="ticked mt-5 space-y-2.5">@foreach ($job->dutyList() as $d)<li>{{ $d }}</li>@endforeach</ul></div>
        @endif
        @if ($job->requirementList())
            <div><x-lanyard code="02">You bring</x-lanyard><ul class="ticked mt-5 space-y-2.5">@foreach ($job->requirementList() as $d)<li>{{ $d }}</li>@endforeach</ul></div>
        @endif
    </section>
    <section class="wrap mt-12">
        <dl class="panel grid gap-4 p-6 sm:grid-cols-3">
            <div><dt class="kicker text-ink-3">Location</dt><dd class="mt-1 font-semibold">{{ $job->location }}</dd></div>
            <div><dt class="kicker text-ink-3">Contract</dt><dd class="mt-1 font-semibold">{{ $job->employment_type->label() }}</dd></div>
            <div><dt class="kicker text-ink-3">{{ $job->role_type->needsPsa() ? 'Licence' : 'Checks' }}</dt><dd class="mt-1 font-semibold">{{ $job->role_type->needsPsa() ? 'Current PSA licence needed' : 'Right to work; Garda vetting after offer' }}</dd></div>
        </dl>
    </section>
    @if ($job->is_demo)<p class="wrap mt-10"><span class="demo-badge">Sample posting for the demo</span></p>@endif
    <section class="wrap mt-12"><a href="{{ route('careers.apply', ['job' => $job->slug]) }}" class="btn btn-primary">Apply for this job<x-icon name="arrow-right" class="nudge h-4 w-4" /></a></section>
</x-layouts.site>
