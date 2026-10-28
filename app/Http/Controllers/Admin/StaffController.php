<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Region;
use App\Enums\RoleType;
use App\Enums\VettingStatus;
use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** HR's register of staff PSA licences, Garda vetting and right-to-work checks. */
class StaffController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StaffMember::class);
        $filters = $this->filters($request);

        return view('admin.staff.index', [
            'staff' => StaffMember::query()->filter($filters)
                ->orderByRaw('case when psa_licence_expires_on is null then 1 else 0 end')->orderBy('psa_licence_expires_on')->orderBy('last_name')
                ->paginate(30)->withQueryString(),
            'filters' => $filters,
            'dueCount' => StaffMember::licenceDue()->count(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', StaffMember::class);

        return view('admin.staff.form', ['staff' => new StaffMember(['role_type' => RoleType::SecurityOfficer, 'region' => Region::Galway, 'active' => true, 'vetting_status' => VettingStatus::NotStarted])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', StaffMember::class);
        $staff = StaffMember::create($this->validated($request));
        $staff->log('Added to the staff register.', 'system', $this->user($request));

        return to_route('admin.staff.show', $staff)->with('status', "{$staff->full_name} added.");
    }

    public function show(StaffMember $staff): View
    {
        Gate::authorize('view', $staff);

        return view('admin.staff.show', ['staff' => $staff->load(['notes.user', 'application'])]);
    }

    public function edit(StaffMember $staff): View
    {
        Gate::authorize('update', $staff);

        return view('admin.staff.form', ['staff' => $staff]);
    }

    public function update(Request $request, StaffMember $staff): RedirectResponse
    {
        Gate::authorize('update', $staff);
        $before = $staff->psa_licence_expires_on?->format('Y-m-d');
        $staff->fill($this->validated($request, $staff))->save();
        $after = $staff->psa_licence_expires_on?->format('Y-m-d');
        if ($before !== $after) {
            $staff->log('PSA licence expiry changed from '.($before ?? 'none').' to '.($after ?? 'none').'; reminders reset.', 'status', $this->user($request));
        }

        return to_route('admin.staff.show', $staff)->with('status', 'Saved.');
    }

    public function note(Request $request, StaffMember $staff): RedirectResponse
    {
        Gate::authorize('update', $staff);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $staff->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', StaffMember::class);

        return $this->csv('allpro-staff-licences', ['Employee no.', 'Name', 'Role', 'Region', 'Active', 'PSA licence', 'PSA expiry', 'Licence state', 'Vetting', 'Vetting cleared', 'Right to work checked'],
            StaffMember::query()->filter($this->filters($request))->lazyById(500)->map(fn (StaffMember $s) => [
                $s->employee_no, $s->full_name, $s->role_type->label(), $s->region->label(), $s->active ? 'yes' : 'no', $s->psa_licence_number,
                $s->psa_licence_expires_on?->format('Y-m-d'), $s->psaState(), $s->vetting_status->label(), $s->vetting_cleared_on?->format('Y-m-d'), $s->rtw_checked_on?->format('Y-m-d'),
            ]));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?StaffMember $staff = null): array
    {
        $request->merge(['psa_licence_number' => preg_replace('/\s+/', '', input_string($request->input('psa_licence_number'))) ?: null]);
        $data = $request->validate([
            'employee_no' => ['required', 'string', 'max:20', Rule::unique('staff_members', 'employee_no')->ignore($staff?->id)],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'role_type' => ['required', Rule::enum(RoleType::class)],
            'region' => ['required', Rule::enum(Region::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'started_on' => ['nullable', 'date_format:Y-m-d'],
            'psa_licence_number' => [RoleType::tryFrom(input_string($request->input('role_type')))?->needsPsa() ? 'required' : 'nullable', 'nullable', 'regex:/^\d{5,10}$/'],
            'psa_licence_expires_on' => ['nullable', 'required_with:psa_licence_number', 'date_format:Y-m-d'],
            'vetting_status' => ['required', Rule::enum(VettingStatus::class)],
            'vetting_cleared_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'rtw_checked_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], ['psa_licence_number.required' => 'Security officers need a PSA licence number.', 'psa_licence_number.regex' => 'Digits only, as on the PSA card.']);

        return $data + ['active' => $request->boolean('active')];
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        return [
            'role' => RoleType::tryFrom(input_string($request->query('role')))?->value,
            'region' => Region::tryFrom(input_string($request->query('region')))?->value,
            'due' => $request->boolean('due') ? '1' : null,
            'inactive' => $request->boolean('inactive') ? '1' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
