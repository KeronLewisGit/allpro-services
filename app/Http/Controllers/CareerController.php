<?php

namespace App\Http\Controllers;

use App\Enums\Region;
use App\Enums\RoleType;
use App\Http\Requests\StoreApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Support\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerController extends Controller
{
    public function index(Request $request): View
    {
        $region = Region::tryFrom(input_string($request->query('region')));
        $role = RoleType::tryFrom(input_string($request->query('role')));
        $all = JobPosting::open()->get();

        return view('pages.careers.index', [
            'jobs' => $all->filter(fn (JobPosting $j) => (! $region || $j->region === $region) && (! $role || $j->role_type === $role))
                ->sortBy([['region', 'asc'], ['title', 'asc']])->values(),
            'all' => $all,
            'region' => $region,
            'role' => $role,
        ]);
    }

    public function show(JobPosting $job): View
    {
        abort_unless($job->isOpen(), 404);

        return view('pages.careers.show', ['job' => $job]);
    }

    public function create(Request $request): View
    {
        $jobs = JobPosting::open()->orderBy('region')->orderBy('title')->get();

        return view('pages.careers.apply', [
            'jobs' => $jobs,
            'selected' => $jobs->firstWhere('slug', input_string($request->query('job'))),
            'presetRole' => RoleType::tryFrom(input_string($request->query('role'))),
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        if (Intake::isBot($request, JobApplication::fakeReference(), 'Job application')) {
            $request->releaseHeldUploads();

            return to_route('thanks');
        }

        $data = $request->applicationData();
        if ($file = $request->file('cv')) {
            // Random name on the private disk; the original name is shown to HR only.
            $data += [
                'cv_path' => $file->store('cvs', 'local'),
                'cv_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'cv_mime' => $file->getMimeType(),
                'cv_size' => (int) $file->getSize(),
            ];
        }
        $application = JobApplication::create($data + ['consented_at' => now()]);

        $request->releaseHeldUploads();
        Intake::complete($application->refresh()->load('jobPosting'), $request);

        return to_route('thanks');
    }
}
