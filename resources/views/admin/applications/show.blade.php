@php
    use App\Enums\ApplicationStatus;
    use App\Enums\VettingStatus;
    $missing = $application->outstandingChecks();
    $a = $application;
@endphp
<x-layouts.admin :title="$a->full_name">
    <x-slot:actions>
        <a href="{{ route('admin.applications.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Applications</a>
        @if ($a->email)<a href="mailto:{{ $a->email }}" class="a-btn a-btn-ghost"><x-icon name="mail" class="h-4 w-4" />Email</a>@endif
        <a href="{{ tel_href($a->phone) }}" class="a-btn a-btn-primary"><x-icon name="phone" class="h-4 w-4" />Call</a>
    </x-slot:actions>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <section class="a-card min-w-0 p-5 md:p-6">
                <div class="flex flex-wrap items-center gap-2"><x-status :status="$a->status" /><span class="chip">{{ $a->region->code() }} · {{ $a->region->label() }}</span></div>
                <h2 class="mt-3 text-xl font-bold">{{ $a->position() }}</h2>
                <dl class="dl-grid mt-5">
                    <dt>Reference</dt><dd class="num">{{ $a->reference }}</dd>
                    <dt>Role</dt><dd>{{ $a->role_type->label() }}</dd>
                    <dt>Experience</dt><dd>{{ $a->experience->label() }}</dd>
                    <dt>Hours wanted</dt><dd>{{ $a->employment_type?->label() ?? 'Not given' }}</dd>
                    <dt>Phone</dt><dd><a class="text-link underline" href="{{ tel_href($a->phone) }}">{{ format_phone($a->phone) }}</a></dd>
                    <dt>Email</dt><dd>{{ $a->email ?? '—' }}</dd>
                    <dt>County</dt><dd>{{ $a->county ?? '—' }}</dd>
                    <dt>Drives</dt><dd>{{ $a->drives === null ? 'Not given' : ($a->drives ? 'Yes, full licence' : 'No') }}</dd>
                    <dt>Right to work</dt><dd>{{ $a->right_to_work->label() }} <span class="text-ink-3">(declared)</span></dd>
                    <dt>Available from</dt><dd>{{ $a->available_from?->format('j F Y') ?? 'Not given' }}</dd>
                    <dt>Received</dt><dd>{{ $a->created_at->format('j M Y, H:i') }}</dd>
                </dl>
                @if ($a->message)<h3 class="mt-5 font-semibold">Message</h3><p class="measure mt-1 whitespace-pre-line text-ink-2">{{ $a->message }}</p>@endif
                <h3 class="mt-6 font-semibold">Shifts they can work</h3>
                <p class="mb-2 text-sm text-ink-3">{{ $a->availabilitySummary() }}</p>
                <x-availability :selected="$a->availability ?? []" :readonly="true" />
                <div class="mt-6 border-t border-line pt-5">
                    @if ($a->cv_path)
                        <a href="{{ route('admin.applications.cv', $a) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Download CV ({{ file_size($a->cv_size) }})</a>
                        <p class="mt-2 text-xs text-ink-3"><x-icon name="lock" class="inline h-3.5 w-3.5" /> HR and administrators only; each download is logged.</p>
                    @else
                        <p class="text-ink-3">No CV attached.</p>
                    @endif
                </div>
            </section>
        </div>
        <div class="min-w-0 space-y-6">
            <section class="a-card p-5">
                <h2 class="font-semibold">Status</h2>
                <form method="POST" action="{{ route('admin.applications.update', $a) }}" class="mt-3 flex flex-wrap gap-2">
                    @csrf @method('PATCH')
                    <label for="a-status" class="sr-only">Status</label>
                    <select id="a-status" name="status" class="a-input min-w-0 flex-1">@foreach (ApplicationStatus::cases() as $s)<option value="{{ $s->value }}" @selected($a->status === $s)>{{ $s->label() }}</option>@endforeach</select>
                    <button class="a-btn a-btn-primary">Save</button>
                </form>
                @if ($missing)
                    <div class="mt-4 rounded-lg bg-surface-2 p-3 text-sm"><p class="font-semibold">Before marking as hired</p><ul class="mt-1 list-disc pl-5 text-ink-2">@foreach ($missing as $m)<li>{{ $m }}</li>@endforeach</ul></div>
                @endif
                @if ($a->status === ApplicationStatus::Hired && auth()->user()->works(\App\Enums\Area::Staff))
                    @if ($a->staffMember)
                        <a href="{{ route('admin.staff.show', $a->staffMember) }}" class="a-btn a-btn-ghost mt-4 w-full"><x-icon name="badge" class="h-4 w-4" />On the staff register as {{ $a->staffMember->employee_no }}</a>
                    @else
                        <form method="POST" action="{{ route('admin.applications.convert', $a) }}" class="mt-4 flex flex-wrap items-end gap-2 border-t border-line pt-4">
                            @csrf
                            <div class="min-w-0 flex-1"><label for="a-emp" class="mb-1 block text-sm text-ink-2">Employee number</label><input id="a-emp" name="employee_no" class="a-input w-full" required maxlength="20" value="{{ old('employee_no') }}"></div>
                            <button class="a-btn a-btn-primary">Add to staff register</button>
                        </form>
                    @endif
                @endif
            </section>

            <section class="a-card p-5" aria-labelledby="checks-title">
                <h2 id="checks-title" class="font-semibold">Checks recorded by HR</h2>
                <p class="mt-1 text-sm text-ink-3">Documents are seen in person and vetting runs through the NVB e-vetting system. Nothing is uploaded here.</p>
                @if ($a->role_type->asksPsa())
                    <div class="mt-4 rounded-lg border border-line p-3">
                        <p class="flex items-center justify-between gap-2"><span class="font-semibold">PSA licence</span>@include('admin.partials.psa-chip', ['state' => $a->psaState()])</p>
                        @if ($a->psa_licence_number)
                            <p class="num mt-1 text-sm">No. {{ $a->psa_licence_number }} · expires {{ $a->psa_licence_expires_on?->format('j M Y') }}</p>
                            <a class="mt-1 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-link" href="https://www.psa.gov.ie" target="_blank" rel="noopener">Check the PSA register<x-icon name="external" class="h-3.5 w-3.5" /></a>
                        @else
                            <p class="mt-1 text-sm text-ink-3">No licence given{{ $a->role_type->needsPsa() ? '' : ' (optional for supervisors)' }}.</p>
                        @endif
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.applications.checks', $a) }}" class="mt-4 space-y-3">
                    @csrf @method('PATCH')
                    <label class="flex min-h-11 items-center gap-3"><input type="checkbox" name="rtw_checked" value="1" class="h-5 w-5 accent-[var(--primary)]" @checked($a->rtw_checked_at)><span>Right-to-work documents seen @if($a->rtw_checked_at)<span class="block text-xs text-ink-3">{{ $a->rtw_checked_at->format('j M Y') }} · {{ $a->rtwChecker->name ?? 'System' }}</span>@endif</span></label>
                    @if ($a->role_type->asksPsa() && $a->psa_licence_number)
                        <label class="flex min-h-11 items-center gap-3"><input type="checkbox" name="psa_checked" value="1" class="h-5 w-5 accent-[var(--primary)]" @checked($a->psa_checked_at)><span>Licence confirmed on the PSA register @if($a->psa_checked_at)<span class="block text-xs text-ink-3">{{ $a->psa_checked_at->format('j M Y') }} · {{ $a->psaChecker->name ?? 'System' }}</span>@endif</span></label>
                    @endif
                    <div><label for="a-vet" class="mb-1 block text-sm text-ink-2">Garda vetting</label>
                        <select id="a-vet" name="vetting_status" class="a-input w-full">@foreach (VettingStatus::cases() as $v)<option value="{{ $v->value }}" @selected($a->vetting_status === $v)>{{ $v->label() }}</option>@endforeach</select>
                        @if ($a->vetting_updated_at)<p class="mt-1 text-xs text-ink-3">Last changed {{ $a->vetting_updated_at->format('j M Y') }}</p>@endif
                    </div>
                    <button class="a-btn a-btn-primary">Save checks</button>
                </form>
            </section>
            @include('admin.partials.timeline', ['notes' => $a->notes, 'action' => route('admin.applications.note', $a)])
        </div>
    </div>
</x-layouts.admin>
