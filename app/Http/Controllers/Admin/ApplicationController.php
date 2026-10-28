<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\Region;
use App\Enums\RoleType;
use App\Enums\VettingStatus;
use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\StaffMember;
use App\Support\Availability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The careers pipeline by region and role. HR records the checks the website never collects:
 * right to work (documents seen in person), the PSA register check and Garda vetting.
 */
class ApplicationController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', JobApplication::class);
        $filters = $this->filters($request);
        $counts = JobApplication::query()->filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        // Pipeline board: open applications by region (rows) and stage (columns), for the current role filter.
        $board = JobApplication::query()->filter(array_merge($filters, ['status' => null, 'region' => null]))
            ->selectRaw('region, status, count(*) as total')->groupBy('region', 'status')->get()
            ->groupBy(fn (JobApplication $a) => $a->region->value)
            ->map(fn ($rows) => $rows->mapWithKeys(fn (JobApplication $a) => [$a->status->value => (int) $a->getAttribute('total')]));

        return view('admin.applications.index', [
            'applications' => JobApplication::with('jobPosting')->filter($filters)
                ->orderByRaw("case when status = 'new' then 0 else 1 end")->orderByDesc('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'counts' => $counts,
            'board' => $board,
            'jobs' => JobPosting::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function show(JobApplication $application): View
    {
        Gate::authorize('view', $application);

        return view('admin.applications.show', ['application' => $application->load(['jobPosting', 'notes.user', 'staffMember', 'psaChecker', 'rtwChecker'])]);
    }

    public function update(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $data = $request->validate(['status' => ['required', Rule::enum(ApplicationStatus::class)]]);
        $status = ApplicationStatus::from($data['status']);
        if ($status === ApplicationStatus::Hired && ($missing = $application->outstandingChecks()) !== []) {
            return back()->withErrors(['status' => 'Can\'t mark as hired yet: '.implode('; ', $missing).'.']);
        }
        if ($application->claimStatus($status)) {
            $application->log('Status changed to '.$status->label().'.', 'status', $this->user($request));
        }

        return back()->with('status', "{$application->reference} saved.");
    }

    /** Right to work, PSA register check and Garda vetting stage, recorded by HR. */
    public function checks(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $data = $request->validate([
            'vetting_status' => ['required', Rule::enum(VettingStatus::class)],
            'rtw_checked' => ['nullable', 'boolean'],
            'psa_checked' => ['nullable', 'boolean'],
        ]);
        $user = $this->user($request);
        $vetting = VettingStatus::from($data['vetting_status']);
        if ($vetting !== $application->vetting_status) {
            $application->forceFill(['vetting_status' => $vetting, 'vetting_updated_at' => now()]);
            $application->log('Garda vetting: '.$vetting->label().'.', 'status', $user);
        }
        if ($request->boolean('rtw_checked') && $application->rtw_checked_at === null) {
            $application->forceFill(['rtw_checked_at' => now(), 'rtw_checked_by' => $user->id]);
            $application->log('Right-to-work documents seen and checked.', 'status', $user);
        } elseif (! $request->boolean('rtw_checked') && $application->rtw_checked_at !== null) {
            $application->forceFill(['rtw_checked_at' => null, 'rtw_checked_by' => null]);
            $application->log('Right-to-work check cleared.', 'status', $user);
        }
        if ($application->role_type->asksPsa() && $application->psa_licence_number) {
            if ($request->boolean('psa_checked') && $application->psa_checked_at === null) {
                $application->forceFill(['psa_checked_at' => now(), 'psa_checked_by' => $user->id]);
                $application->log("PSA licence {$application->psa_licence_number} checked on the PSA register.", 'status', $user);
            } elseif (! $request->boolean('psa_checked') && $application->psa_checked_at !== null) {
                $application->forceFill(['psa_checked_at' => null, 'psa_checked_by' => null]);
                $application->log('PSA register check cleared.', 'status', $user);
            }
        }
        $application->save();

        return back()->with('status', 'Checks saved.');
    }

    /** A hired applicant goes onto the staff register, carrying their licence and checks. */
    public function convert(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        Gate::authorize('create', StaffMember::class);
        abort_unless($application->status === ApplicationStatus::Hired, 422, 'Only hired applicants go on the staff register.');
        if ($application->staffMember) {
            return to_route('admin.staff.show', $application->staffMember);
        }
        $data = $request->validate(['employee_no' => ['required', 'string', 'max:20', Rule::unique('staff_members', 'employee_no')]]);
        $staff = StaffMember::create([
            'employee_no' => $data['employee_no'],
            'first_name' => $application->first_name,
            'last_name' => $application->last_name,
            'role_type' => $application->role_type,
            'region' => $application->region,
            'phone' => $application->phone,
            'email' => $application->email,
            'started_on' => $application->available_from ?? today(),
            'psa_licence_number' => $application->psa_licence_number,
            'psa_licence_expires_on' => $application->psa_licence_expires_on,
            'vetting_status' => $application->vetting_status,
            'vetting_cleared_on' => $application->vetting_status === VettingStatus::Cleared ? $application->vetting_updated_at : null,
            'rtw_checked_on' => $application->rtw_checked_at,
            'job_application_id' => $application->id,
        ]);
        $user = $this->user($request);
        $staff->log("Added from application {$application->reference}.", 'system', $user);
        $application->log("Added to the staff register as {$staff->employee_no}.", 'status', $user);

        return to_route('admin.staff.show', $staff)->with('status', "{$staff->full_name} is on the staff register.");
    }

    public function note(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $application->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    public function cv(Request $request, JobApplication $application): StreamedResponse
    {
        Gate::authorize('openCv', $application);
        abort_unless($application->cv_path && Storage::disk('local')->exists($application->cv_path), 404);
        $application->log('Opened the CV.', 'file', $this->user($request));
        $name = Str::slug($application->reference.'-'.$application->full_name.'-cv').'.'.pathinfo($application->cv_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($application->cv_path, $name, ['Cache-Control' => 'private, no-store']);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', JobApplication::class);
        $query = JobApplication::with('jobPosting')->filter($this->filters($request));

        return $this->csv('allpro-applications', ['Reference', 'Received', 'Status', 'Name', 'Role', 'Region', 'Position', 'County', 'Experience', 'Right to work', 'Availability', 'PSA licence', 'PSA expiry', 'Vetting', 'Phone', 'Email'],
            $query->lazyByIdDesc(500)->map(fn (JobApplication $a) => [
                $a->reference, $a->created_at->format('Y-m-d'), $a->status->label(), $a->full_name, $a->role_type->label(), $a->region->label(), $a->position(),
                $a->county, $a->experience->label(), $a->right_to_work->short(), $a->availabilitySummary(), $a->psa_licence_number, $a->psa_licence_expires_on?->format('Y-m-d'),
                $a->vetting_status->label(), format_phone($a->phone), $a->email,
            ]));
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $job = input_string($request->query('job'));
        $shift = input_string($request->query('shift'));

        return [
            'status' => ApplicationStatus::tryFrom(input_string($request->query('status')))?->value,
            'role' => RoleType::tryFrom(input_string($request->query('role')))?->value,
            'region' => Region::tryFrom(input_string($request->query('region')))?->value,
            'job' => ctype_digit($job) ? $job : null,
            'shift' => array_key_exists($shift, Availability::SHIFTS) ? $shift : null,
            'waiting' => $request->boolean('waiting') ? '1' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
