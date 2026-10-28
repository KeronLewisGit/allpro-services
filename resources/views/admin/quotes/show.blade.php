@php use App\Enums\QuoteStatus; @endphp
<x-layouts.admin :title="$quote->organisation">
    <x-slot:actions>
        <a href="{{ route('admin.quotes.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Quote requests</a>
        <a href="mailto:{{ $quote->email }}?subject={{ rawurlencode('Your site survey: '.$quote->reference) }}" class="a-btn a-btn-ghost"><x-icon name="mail" class="h-4 w-4" />Email</a>
        <a href="{{ tel_href($quote->phone) }}" class="a-btn a-btn-primary"><x-icon name="phone" class="h-4 w-4" />Call</a>
    </x-slot:actions>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <section class="a-card p-5 md:p-6">
                <div class="flex flex-wrap items-center gap-2"><x-status :status="$quote->status" /><span class="chip">{{ $quote->region->code() }} · {{ $quote->region->label() }}</span>@if($quote->is_tender)<x-chip tone="attention" icon="file">Tender</x-chip>@endif</div>
                <h2 class="mt-3 text-xl font-bold">{{ $quote->site_name }}, {{ $quote->town }}</h2>
                <p class="mt-1 text-ink-2">{{ implode(' · ', $quote->serviceLabels()) }}</p>
                <dl class="dl-grid mt-5">
                    <dt>Reference</dt><dd class="num">{{ $quote->reference }}</dd>
                    <dt>Sector</dt><dd>{{ allpro('sectors.'.$quote->sector) }}</dd>
                    <dt>Site address</dt><dd>{{ $quote->site_address }}, {{ $quote->town }}, Co. {{ $quote->county }}{{ $quote->eircode ? ', '.$quote->eircode : '' }}</dd>
                    <dt>Sites</dt><dd class="num">{{ $quote->site_count }}</dd>
                    <dt>Floor area</dt><dd class="num">{{ $quote->floor_area_m2 ? number_format($quote->floor_area_m2).' m²' : 'Not given' }}</dd>
                    <dt>Cover</dt><dd class="whitespace-pre-line">{{ $quote->cover ?: 'Not given' }}</dd>
                    <dt>Start</dt><dd>{{ $quote->start_on?->format('F Y') ?? 'Not given' }}</dd>
                    <dt>Survey requested</dt><dd>{{ $quote->survey_on->format('l j F Y') }}, {{ \Illuminate\Support\Str::lower(\App\Models\QuoteRequest::WINDOWS[$quote->survey_window] ?? $quote->survey_window) }}</dd>
                    @if ($quote->is_tender)<dt>Tender</dt><dd>{{ $quote->tender_reference ?: 'No reference given' }}{{ $quote->tender_closes_on ? ', closes '.$quote->tender_closes_on->format('j M Y') : '' }}</dd>@endif
                    <dt>Contact</dt><dd>{{ $quote->contact_name }}{{ $quote->contact_role ? ', '.$quote->contact_role : '' }}</dd>
                    <dt>Phone</dt><dd><a class="text-link underline" href="{{ tel_href($quote->phone) }}">{{ format_phone($quote->phone) }}</a></dd>
                    <dt>Email</dt><dd><a class="text-link underline" href="mailto:{{ $quote->email }}">{{ $quote->email }}</a></dd>
                    <dt>Received</dt><dd>{{ $quote->created_at->format('j M Y, H:i') }}</dd>
                </dl>
                @if ($quote->message)<h3 class="mt-6 font-semibold">Message</h3><p class="measure mt-1 whitespace-pre-line text-ink-2">{{ $quote->message }}</p>@endif
                <h3 class="mt-6 font-semibold">Files</h3>
                @forelse ($quote->files as $f)
                    <a href="{{ route('admin.quotes.file', [$quote, $f]) }}" class="mt-2 flex min-h-11 items-center gap-2 text-link hover:underline"><x-icon name="file" class="h-4 w-4" />{{ $f->original_name }} <span class="text-sm text-ink-3">{{ file_size($f->size) }}</span></a>
                @empty
                    <p class="mt-1 text-ink-3">None attached.</p>
                @endforelse
            </section>
        </div>
        <div class="min-w-0 space-y-6">
            <section class="a-card p-5">
                <h2 class="font-semibold">Owner, survey and quote</h2>
                <form method="POST" action="{{ route('admin.quotes.update', $quote) }}" class="mt-3 space-y-4">
                    @csrf @method('PATCH')
                    <div><label for="q-owner" class="mb-1 block text-sm text-ink-2">Sales owner</label>
                        <select id="q-owner" name="assigned_to" class="a-input w-full"><option value="">Unassigned</option>@foreach ($owners as $o)<option value="{{ $o->id }}" @selected($quote->assigned_to === $o->id)>{{ $o->name }}{{ $o->region ? ' ('.$o->region->label().')' : '' }}</option>@endforeach</select></div>
                    <div><label for="q-survey" class="mb-1 block text-sm text-ink-2">Survey booked for</label>
                        <input id="q-survey" type="datetime-local" name="survey_at" value="{{ old('survey_at', $quote->survey_at?->format('Y-m-d\TH:i')) }}" class="a-input w-full"></div>
                    <div><label for="q-value" class="mb-1 block text-sm text-ink-2">Quoted value, € a year</label>
                        <input id="q-value" type="number" step="0.01" min="0" name="quoted_value" value="{{ old('quoted_value', $quote->quoted_value) }}" class="a-input w-full" inputmode="decimal"></div>
                    <div><label for="q-status" class="mb-1 block text-sm text-ink-2">Status</label>
                        <select id="q-status" name="status" class="a-input w-full">@foreach (QuoteStatus::cases() as $s)<option value="{{ $s->value }}" @selected($quote->status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
                    <button class="a-btn a-btn-primary">Save</button>
                </form>
            </section>
            @include('admin.partials.timeline', ['notes' => $quote->notes, 'action' => route('admin.quotes.note', $quote)])
        </div>
    </div>
</x-layouts.admin>
