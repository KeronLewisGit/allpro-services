<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\EnquiryStatus;
use App\Enums\EnquiryTopic;
use App\Enums\Experience;
use App\Enums\JobStatus;
use App\Enums\QuoteStatus;
use App\Enums\Region;
use App\Enums\RightToWork;
use App\Enums\Role;
use App\Enums\RoleType;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\VettingStatus;
use App\Models\ClientAccount;
use App\Models\Enquiry;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\QuoteRequest;
use App\Models\ServiceTicket;
use App\Models\StaffMember;
use App\Models\User;
use App\Support\Availability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data for the pitch: every organisation, person, site and ticket here is fictional and
 * labelled "(sample)" or "Sample". No real Allpro client is named. Logins:
 * admin|sales|hr|operations@allpro.test (+ sales.dublin|sales.limerick|sales.cork) / password.
 * Never run in production.
 */
class DemoSeeder extends Seeder
{
    private const array FIRST = ['Aoife', 'Ciarán', 'Siobhán', 'Darragh', 'Niamh', 'Eoin', 'Gráinne', 'Cian', 'Orla', 'Seán', 'Róisín', 'Tomasz', 'Ana', 'Pádraig', 'Ewa', 'Declan', 'Maria', 'Fionn', 'Lucia', 'Kevin', 'Sinéad', 'Andrei'];

    private const array LAST = ['Murphy', 'Kelly', 'Byrne', 'Ryan', "O'Brien", 'Walsh', "O'Connor", 'McCarthy', 'Doyle', 'Lynch', 'Kowalski', 'Gallagher', 'Popescu', 'Fahy', 'Burke', 'Silva', 'Keane', 'Flaherty'];

    public function run(): void
    {
        // Fictional data and "password" logins: never on the live site. A pitch preview runs
        // APP_ENV=production with ALLPRO_PREVIEW=true, so it is allowed there.
        if (app()->isProduction() && ! allpro('preview')) {
            $this->command?->error('DemoSeeder does not run in production unless ALLPRO_PREVIEW=true (a pitch preview).');

            return;
        }
        mt_srand(353);
        $staff = $this->users();
        $this->careers();
        $this->register();
        $this->quotes($staff);
        $this->desk($staff);
        $this->enquiries();
    }

    /** @return array<string, User> */
    private function users(): array
    {
        $out = [];
        foreach ([
            'admin' => ['Demo Administrator', Role::Admin, null],
            'sales' => ['Demo Sales, Galway', Role::Sales, Region::Galway],
            'sales.dublin' => ['Demo Sales, Dublin', Role::Sales, Region::Dublin],
            'sales.limerick' => ['Demo Sales, Limerick', Role::Sales, Region::Limerick],
            'sales.cork' => ['Demo Sales, Cork', Role::Sales, Region::Cork],
            'hr' => ['Demo HR Officer', Role::Hr, null],
            'operations' => ['Demo Operations Manager', Role::Operations, null],
            'operations.dublin' => ['Demo Contracts Supervisor', Role::Operations, Region::Dublin],
        ] as $key => [$name, $role, $region]) {
            $out[$key] = User::updateOrCreate(['email' => $key.'@allpro.test'], ['name' => $name, 'password' => Hash::make('password'), 'role' => $role, 'region' => $region, 'active' => true]);
        }

        return $out;
    }

    private function name(): string
    {
        return self::FIRST[mt_rand(0, count(self::FIRST) - 1)].' '.self::LAST[mt_rand(0, count(self::LAST) - 1)];
    }

    /** Irish mobile format, 555 exchange, clearly sample. */
    private function phone(): string
    {
        return '08'.[3, 5, 6, 7][mt_rand(0, 3)].' 555 '.str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function psa(): string
    {
        return (string) mt_rand(10000, 99999).str_pad((string) mt_rand(0, 99), 2, '0', STR_PAD_LEFT);
    }

    /** @return list<string> */
    private function shifts(): array
    {
        $keys = Availability::keys();
        $pattern = [
            ['mon-early', 'tue-early', 'wed-early', 'thu-early', 'fri-early'],
            ['mon-late', 'tue-late', 'wed-late', 'thu-late', 'fri-late', 'sat-late'],
            ['fri-night', 'sat-night', 'sun-night', 'mon-night'],
            ['sat-early', 'sat-late', 'sun-early', 'sun-late'],
            ['mon-early', 'wed-early', 'fri-early', 'tue-late', 'thu-late'],
        ][mt_rand(0, 4)];

        return array_values(array_intersect($keys, $pattern));
    }

    /** A tiny valid PDF on the private disk, so "open" works in the demo. */
    private function pdf(string $path, string $title): void
    {
        $text = str_replace(['(', ')', '\\'], '', 'SAMPLE DOCUMENT - '.$title);
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        Storage::disk('local')->put($path, $pdf);
    }

    private function at(Carbon $time, callable $create): mixed
    {
        Carbon::setTestNow($time);
        try {
            return $create();
        } finally {
            Carbon::setTestNow();
        }
    }

    private function careers(): void
    {
        $jobs = [
            ['Security officer, Galway city (sample)', RoleType::SecurityOfficer, Region::Galway, EmploymentType::FullTime, 'Galway city', '40 hours, rotating days and nights', 3,
                'Static guarding at an office and retail site in Galway city: access control, patrols and incident reports.', "Control access and sign visitors in\nCarry out and log patrols\nWrite clear incident and shift reports", "Current PSA licence (door supervisor or static guarding)\nGood spoken and written English\nAble to stand and walk for a full shift"],
            ['Cleaning operative, early mornings (sample)', RoleType::CleaningOperative, Region::Galway, EmploymentType::PartTime, 'Oranmore and Ballybrit', '6 am to 9 am, Monday to Friday', 4,
                'Office and washroom cleaning before staff arrive, on business parks east of Galway city.', "Clean offices, kitchens and washrooms to a checklist\nRestock consumables\nReport faults and hazards", "Reliable and on time\nPrevious cleaning experience helpful, not essential\nTraining provided"],
            ['Cleaning operative, evenings (sample)', RoleType::CleaningOperative, Region::Dublin, EmploymentType::PartTime, 'Dublin 2 and Dublin 4', '5.30 pm to 9.30 pm, Monday to Friday', 6,
                'Evening office cleaning in city-centre buildings.', "Vacuum, dust and empty bins\nClean and restock washrooms\nUse floor machines once trained", "Previous cleaning experience helpful\nAble to work alone and in a small team"],
            ['Security officer, mobile patrol (sample)', RoleType::SecurityOfficer, Region::Dublin, EmploymentType::FullTime, 'North Dublin', 'Nights, 4 on 4 off', 2,
                'Mobile patrols and alarm response across business parks in north Dublin.', "Patrol client sites by van to a schedule\nRespond to alarm activations\nKey holding and lock-up", "Current PSA licence\nFull, clean driving licence\nNight work"],
            ['Cleaning supervisor (sample)', RoleType::Supervisor, Region::Limerick, EmploymentType::FullTime, 'Limerick city and Shannon', '39 hours, days', 1,
                'Lead cleaning teams across several client sites and keep standards up.', "Plan rosters and cover absences\nAudit sites against the cleaning specification\nTrain new operatives", "Two years cleaning experience, one as a team leader\nFull driving licence\nPSA licence an advantage for mixed sites"],
            ['Relief cleaning operative (sample)', RoleType::CleaningOperative, Region::Cork, EmploymentType::Relief, 'Cork city and Little Island', 'Cover shifts, as needed', 5,
                'Cover holidays and absences on cleaning contracts around Cork.', "Clean to the site checklist\nWork across several sites", "Flexible and reliable\nOwn transport helpful"],
            ['Security supervisor (sample)', RoleType::Supervisor, Region::Cork, EmploymentType::FullTime, 'Cork', '40 hours, days and some weekends', 1,
                'Supervise security officers on static sites in Cork.', "Run shift briefings\nCheck patrol logs and reports\nFirst point of contact for site managers", "Current PSA licence\nThree years in security, one supervising"],
        ];
        $posts = [];
        foreach ($jobs as [$title, $role, $region, $type, $loc, $hours, $openings, $summary, $duties, $req]) {
            $posts[] = JobPosting::create(['slug' => Str::slug(str_replace(' (sample)', '', $title)), 'title' => $title, 'role_type' => $role, 'region' => $region, 'employment_type' => $type,
                'status' => JobStatus::Open, 'location' => $loc, 'hours' => $hours, 'openings' => $openings, 'summary' => $summary, 'duties' => $duties, 'requirements' => $req,
                'closes_on' => today()->addDays(mt_rand(14, 45)), 'is_demo' => true]);
        }
        JobPosting::create(['slug' => 'washroom-technician-closed', 'title' => 'Washroom technician (sample, closed)', 'role_type' => RoleType::CleaningOperative, 'region' => Region::Galway,
            'employment_type' => EmploymentType::FullTime, 'status' => JobStatus::Closed, 'location' => 'Galway', 'openings' => 1, 'summary' => 'Closed sample posting.', 'is_demo' => true]);

        $statuses = [ApplicationStatus::New, ApplicationStatus::New, ApplicationStatus::New, ApplicationStatus::Screening, ApplicationStatus::Interview, ApplicationStatus::Vetting, ApplicationStatus::Offer, ApplicationStatus::Hired, ApplicationStatus::Rejected];
        $counties = ['galway' => ['Galway', 'Mayo'], 'dublin' => ['Dublin', 'Kildare', 'Meath'], 'limerick' => ['Limerick', 'Clare'], 'cork' => ['Cork', 'Kerry']];
        for ($i = 0; $i < 28; $i++) {
            $job = $i % 6 === 5 ? null : $posts[$i % count($posts)];
            $role = $job->role_type ?? [RoleType::CleaningOperative, RoleType::SecurityOfficer][$i % 2];
            $region = $job->region ?? Region::cases()[$i % 4];
            [$first, $last] = explode(' ', $this->name(), 2);
            $status = $statuses[$i % count($statuses)];
            $psa = $role->asksPsa() && ($role->needsPsa() || $i % 2 === 0);
            $app = $this->at(now()->subDays(mt_rand(0, 40))->subHours(mt_rand(0, 10)), fn () => JobApplication::create([
                'job_posting_id' => $job?->id, 'role_type' => $role, 'region' => $region, 'status' => ApplicationStatus::New, 'first_name' => $first, 'last_name' => $last.' (sample)',
                'phone' => $this->phone(), 'email' => $i % 3 === 0 ? null : Str::slug($first.'.'.$last, '.').$i.'@example.ie',
                'county' => $counties[$region->value][$i % count($counties[$region->value])], 'experience' => Experience::cases()[mt_rand(0, 3)],
                'right_to_work' => RightToWork::cases()[$i % 7 === 3 ? 2 : ($i % 3 === 1 ? 1 : 0)], 'employment_type' => $job->employment_type ?? EmploymentType::PartTime,
                'availability' => $this->shifts(), 'drives' => $i % 2 === 0, 'available_from' => today()->addDays(mt_rand(3, 30)),
                'psa_licence_number' => $psa ? $this->psa() : null, 'psa_licence_expires_on' => $psa ? today()->addDays(mt_rand(90, 900)) : null,
                'message' => $i % 4 === 0 ? 'Sample: I have worked nights before and live close to the site.' : null, 'consented_at' => now(),
            ]));
            if ($i % 5 === 0) {
                $path = 'cvs/demo-'.$app->id.'.pdf';
                $this->pdf($path, 'CV - '.$app->full_name);
                $app->forceFill(['cv_path' => $path, 'cv_name' => 'CV.pdf', 'cv_mime' => 'application/pdf', 'cv_size' => 1400])->save();
            }
            $app->log('Received from the website.');
            if ($status === ApplicationStatus::New) {
                continue;
            }
            // Later stages have the checks HR records.
            $later = in_array($status, [ApplicationStatus::Vetting, ApplicationStatus::Offer, ApplicationStatus::Hired], true);
            if ($later) {
                $app->forceFill([
                    'rtw_checked_at' => now()->subDays(3),
                    'psa_checked_at' => $app->psa_licence_number ? now()->subDays(3) : null,
                    'vetting_status' => $status === ApplicationStatus::Vetting ? VettingStatus::Submitted : VettingStatus::Cleared,
                    'vetting_updated_at' => now()->subDays(1),
                ])->save();
                $app->log('Right-to-work documents seen and checked.', 'status');
                $app->log('Garda vetting: '.$app->vetting_status->label().'.', 'status');
            } elseif ($status === ApplicationStatus::Interview) {
                $app->forceFill(['vetting_status' => VettingStatus::Invited, 'vetting_updated_at' => now()->subDays(2)])->save();
            }
            $app->claimStatus($status);
            $app->log('Status changed to '.$status->label().'.', 'status');
        }
    }

    /** The HR licence register: a spread of PSA expiries so the dashboard and reminders have work. */
    private function register(): void
    {
        $expiries = [-12, -3, 9, 21, 34, 47, 58, 75, 120, 160, 210, 260, 300, 340, 400, 450, 500, 560, 620, 700];
        foreach ($expiries as $i => $days) {
            [$first, $last] = explode(' ', $this->name(), 2);
            $role = $i % 5 === 4 ? RoleType::Supervisor : RoleType::SecurityOfficer;
            $s = StaffMember::create([
                'employee_no' => 'S'.str_pad((string) (1040 + $i * 7), 5, '0', STR_PAD_LEFT), 'first_name' => $first, 'last_name' => $last.' (sample)',
                'role_type' => $role, 'region' => Region::cases()[$i % 4], 'phone' => $this->phone(), 'started_on' => today()->subDays(mt_rand(120, 2400)), 'active' => true,
                'psa_licence_number' => $this->psa(), 'psa_licence_expires_on' => today()->addDays($days), 'vetting_status' => VettingStatus::Cleared,
                'vetting_cleared_on' => today()->subDays(mt_rand(200, 1500)), 'rtw_checked_on' => today()->subDays(mt_rand(200, 1500)), 'is_demo' => true,
            ]);
            $s->log('Added to the staff register.', 'system');
        }
        foreach (range(0, 7) as $i) {
            [$first, $last] = explode(' ', $this->name(), 2);
            StaffMember::create([
                'employee_no' => 'C'.str_pad((string) (2100 + $i * 3), 5, '0', STR_PAD_LEFT), 'first_name' => $first, 'last_name' => $last.' (sample)',
                'role_type' => RoleType::CleaningOperative, 'region' => Region::cases()[$i % 4], 'phone' => $this->phone(), 'started_on' => today()->subDays(mt_rand(60, 1800)),
                'active' => $i !== 7, 'vetting_status' => $i % 3 === 0 ? VettingStatus::Cleared : VettingStatus::NotStarted, 'rtw_checked_on' => today()->subDays(mt_rand(60, 900)), 'is_demo' => true,
            ])->log('Added to the staff register.', 'system');
        }
    }

    /** @param array<string, User> $staff */
    private function quotes(array $staff): void
    {
        $rows = self::quoteRows();
        foreach ($rows as $i => [$services, $sector, $org, $site, $address, $town, $county, $area, $surveyIn, $status]) {
            $survey = today()->addWeekdays(max(1, abs($surveyIn)));
            if ($surveyIn < 0) {
                $survey = today()->subWeekdays(abs($surveyIn));
            }
            $created = $surveyIn < 0 ? $survey->copy()->subDays(mt_rand(5, 12)) : now()->subDays(mt_rand(0, 4))->subHours(mt_rand(1, 9));
            $q = $this->at($created, fn () => QuoteRequest::create([
                'services' => $services, 'sector' => $sector, 'organisation' => $org.' (sample)', 'contact_name' => $this->name(), 'contact_role' => ['Facilities manager', 'Office manager', 'Operations director', 'Principal'][$i % 4],
                'email' => 'facilities'.($i + 1).'@example.ie', 'phone' => $this->phone(), 'site_name' => $site, 'site_address' => $address, 'town' => $town, 'county' => $county,
                'eircode' => null, 'site_count' => str_contains($site, 'Three') ? 3 : 1, 'floor_area_m2' => $area,
                'cover' => ['Daily office clean, Monday to Friday after 6 pm.', 'Static guard 24/7, with weekend cover.', 'Weekly visits plus periodic deep cleans.', 'Monthly service, quarterly deep clean.'][$i % 4],
                'start_on' => today()->addMonths(mt_rand(1, 3))->startOfMonth(), 'survey_on' => $survey, 'survey_window' => ['morning', 'afternoon', 'any'][$i % 3],
                'is_tender' => $sector === 'state', 'tender_reference' => $sector === 'state' ? 'SAMPLE-RFT-2026-'.(40 + $i) : null, 'tender_closes_on' => $sector === 'state' ? today()->addDays(24) : null,
                'message' => 'Sample request for the demo.', 'consented_at' => now(), 'source_url' => url('/quote'),
            ]));
            $q->log('Received from the website.');
            if ($q->assignee) {
                $q->log('Assigned to '.$q->assignee->name.' ('.$q->region->label().' sales).', 'assign');
            }
            if ($i === 1) {
                $this->pdf('quotes/'.$q->id.'/floor-plan.pdf', 'Floor plan - '.$q->organisation);
                $q->files()->create(['path' => 'quotes/'.$q->id.'/floor-plan.pdf', 'original_name' => 'Ground floor plan.pdf', 'mime' => 'application/pdf', 'size' => 1400]);
            }
            if ($status !== QuoteStatus::New) {
                $q->update(['survey_at' => $survey->copy()->setTime(10, 0)]);
                if (in_array($status, [QuoteStatus::Quoted, QuoteStatus::Won, QuoteStatus::Lost], true)) {
                    $q->update(['quoted_value' => [38400, 61200, 52800][$i % 3]]);
                }
                $q->claimStatus($status);
                $q->log('Status changed to '.$status->label().'.', 'status', $q->assignee);
            }
        }
        // One request from a county no sales user covers yet would still route; leave one unassigned for the filter.
        QuoteRequest::query()->latest('id')->first()?->update(['assigned_to' => null]);
        unset($staff);
    }

    /** @param array<string, User> $staff */
    private function desk(array $staff): void
    {
        $accounts = [
            ['Sample Technology Campus', 'Blocks A to C', Region::Galway, 'Sample Business Park, Ballybrit, Galway', ['cleaning', 'washroom', 'consumables']],
            ['Sample Retail Park', 'Car park and common areas', Region::Galway, 'Sample Retail Park, Oranmore, Co. Galway', ['security', 'cleaning']],
            ['Sample Financial Services', 'City office', Region::Dublin, 'Sample Quay, Dublin 1', ['cleaning', 'security']],
            ['Sample Pharma', 'Manufacturing site', Region::Cork, 'Sample Industrial Estate, Little Island, Co. Cork', ['facilities']],
            ['Sample Education Trust', 'Secondary school', Region::Limerick, 'Sample Road, Castletroy, Limerick', ['cleaning', 'landscaping']],
        ];
        $made = [];
        foreach ($accounts as $i => [$org, $site, $region, $address, $services]) {
            $a = ClientAccount::create(['organisation' => $org.' (sample)', 'site_name' => $site, 'region' => $region, 'address' => $address, 'contact_name' => $this->name(),
                'contact_email' => 'site'.($i + 1).'@example.ie', 'contact_phone' => $this->phone(), 'services' => $services, 'created_by' => $staff['operations']->id, 'is_demo' => true]);
            $a->shareLink(90);
            $a->log('Client site added.', 'system', $staff['operations']);
            $a->log('Service-desk link created, valid until '.$a->link_expires_at?->format('j M Y').'.', 'system', $staff['operations']);
            $made[] = $a;
        }

        $tickets = self::ticketRows();
        $ops = $staff['operations'];
        foreach ($tickets as [$ai, $type, $cat, $prio, $ago, $status, $desc, $loc]) {
            $account = $made[$ai];
            $logged = now()->subMinutes($ago);
            /** @var ServiceTicket $t */
            $t = $this->at($logged, fn () => $account->tickets()->create(['type' => $type, 'category' => $cat, 'priority' => $prio, 'reporter_name' => $account->contact_name,
                'reporter_email' => $account->contact_email, 'location' => $loc, 'description' => $desc.' (sample)']));
            $t->log('Logged by the client on the service-desk link.');
            if ($status === TicketStatus::New) {
                continue;
            }
            $ackAt = $logged->copy()->addMinutes(min($prio->responseHours() * 60 - 10, 40 + $ago % 50));
            if ($ai === 3 && $status === TicketStatus::InProgress) {
                $ackAt = $logged->copy()->addHours($prio->responseHours() + 2); // one late response, so the SLA chart is honest
            }
            $this->at($ackAt, function () use ($t, $ops): void {
                $t->update(['assigned_to' => $ops->id]);
                $t->claimStatus(TicketStatus::Acknowledged);
                $t->log('Status changed to Acknowledged.', 'status', $ops);
                $t->log('Thanks, we have this. The site supervisor will look at it on the next shift.', 'client', $ops);
            });
            if (in_array($status, [TicketStatus::InProgress, TicketStatus::Resolved, TicketStatus::Closed], true)) {
                $this->at($ackAt->copy()->addMinutes(30), fn () => $t->claimStatus(TicketStatus::InProgress));
            }
            if (in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
                $done = $logged->copy()->addHours(max(1, (int) floor($prio->resolveHours() * 0.6)));
                $this->at($done, function () use ($t, $ops): void {
                    $t->claimStatus(TicketStatus::Resolved);
                    $t->log('Status changed to Resolved.', 'status', $ops);
                    $t->log('Done. Please let us know if anything else needs attention.', 'client', $ops);
                });
                if ($status === TicketStatus::Closed) {
                    $this->at($done->copy()->addDay(), fn () => $t->claimStatus(TicketStatus::Closed));
                }
            }
        }
    }

    /** @return list<array{0: list<string>, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: int|null, 8: int, 9: QuoteStatus}> */
    private static function quoteRows(): array
    {
        return [
            [['cleaning'], 'corporate', 'Sample Software Ltd', 'Head office, 3 floors', 'Unit 4 Sample Business Park', 'Ballybrit', 'Galway', 2400, 3, QuoteStatus::New],
            [['cleaning', 'washroom', 'consumables'], 'medical', 'Sample Medical Centre', 'Primary care centre', '12 Sample Road', 'Tuam', 'Galway', 900, 4, QuoteStatus::New],
            [['security'], 'industrial', 'Sample Logistics', 'Distribution warehouse', 'Sample Industrial Estate', 'Ballycoolin', 'Dublin', 8000, 5, QuoteStatus::New],
            [['facilities'], 'corporate', 'Sample Property Management', 'Business campus, 4 blocks', 'Sample Campus', 'Sandyford', 'Dublin', 15000, 6, QuoteStatus::New],
            [['cleaning', 'industrial'], 'retail', 'Sample Retail Group', 'Shopping centre common areas', 'Main Street', 'Ennis', 'Clare', 5200, 3, QuoteStatus::New],
            [['landscaping'], 'education', 'Sample Community College', 'School grounds', 'College Road', 'Mallow', 'Cork', null, 9, QuoteStatus::New],
            [['security', 'cleaning'], 'construction', 'Sample Builders', 'Residential site, 60 units', 'Sample Lands', 'Ballincollig', 'Cork', null, 4, QuoteStatus::New],
            [['washroom'], 'hospitality', 'Sample Hotel', 'Hotel, 80 bedrooms', 'Quay Street', 'Galway', 'Galway', 3500, 12, QuoteStatus::SurveyBooked],
            [['cleaning'], 'state', 'Sample State Agency', 'Regional office', 'Sample Street', 'Limerick', 'Limerick', 1800, 8, QuoteStatus::SurveyBooked],
            [['pest', 'washroom'], 'hospitality', 'Sample Restaurant Group', 'Three restaurants', 'Oliver Plunkett Street', 'Cork', 'Cork', 600, -6, QuoteStatus::Surveyed],
            [['cleaning', 'consumables'], 'corporate', 'Sample Insurance Services', 'Office, 2 floors', 'Sample Square', 'Dublin 2', 'Dublin', 2100, -14, QuoteStatus::Quoted],
            [['security'], 'retail', 'Sample Outlet Centre', 'Retail park', 'Sample Retail Park', 'Athlone', 'Westmeath', null, -25, QuoteStatus::Won],
            [['cleaning'], 'education', 'Sample Secondary School', 'School, 600 pupils', 'Church Road', 'Nenagh', 'Tipperary', 4200, -30, QuoteStatus::Lost],
            [['industrial'], 'industrial', 'Sample Manufacturing', 'Factory high-level clean', 'Sample Industrial Park', 'Shannon', 'Clare', 6000, 15, QuoteStatus::New],
        ];
    }

    /**
     * [account, type, category, priority, minutes ago, status, description, location]
     *
     * @return list<array{0: int, 1: TicketType, 2: TicketCategory, 3: TicketPriority, 4: int, 5: TicketStatus, 6: string, 7: string|null}>
     */
    private static function ticketRows(): array
    {
        return [
            [0, TicketType::Request, TicketCategory::Washroom, TicketPriority::Urgent, 50, TicketStatus::New, 'Ground floor gents washroom: two toilets blocked and the floor is wet. Staff are using the first floor.', 'Block A, ground floor'],
            [2, TicketType::Incident, TicketCategory::Security, TicketPriority::Urgent, 200, TicketStatus::Acknowledged, 'Side door to the bike store was found unlocked at 7 am. Nothing appears to be missing. Please check the evening lock-up.', 'Rear bike store'],
            [1, TicketType::Complaint, TicketCategory::Cleaning, TicketPriority::High, 230, TicketStatus::New, 'Bins in the car park not emptied since Friday and litter blowing onto the road.', 'Car park, east side'],
            [3, TicketType::Request, TicketCategory::Consumables, TicketPriority::Normal, 60 * 20, TicketStatus::InProgress, 'Hand towel and soap stock running low in all washrooms. Please top up and review the delivery schedule.', null],
            [4, TicketType::Request, TicketCategory::Grounds, TicketPriority::Low, 60 * 30, TicketStatus::New, 'Hedge along the front boundary needs cutting before the open evening.', 'Front entrance'],
            [0, TicketType::Complaint, TicketCategory::Staff, TicketPriority::High, 60 * 5, TicketStatus::InProgress, 'Evening cleaner left before finishing the second floor on Tuesday and Wednesday.', 'Block B, second floor'],
            [2, TicketType::Request, TicketCategory::Cleaning, TicketPriority::Normal, 60 * 50, TicketStatus::Resolved, 'Carpet stain outside the boardroom after a spill. Please arrange a spot clean.', 'Third floor'],
            [1, TicketType::Request, TicketCategory::Pest, TicketPriority::High, 60 * 70, TicketStatus::Closed, 'Signs of mice in the staff kitchen of unit 6.', 'Unit 6 kitchen'],
            [3, TicketType::Incident, TicketCategory::Security, TicketPriority::Urgent, 60 * 96, TicketStatus::Closed, 'Alarm activation at 2.40 am. Patrol attended; false alarm caused by a faulty sensor.', 'Warehouse 2'],
            [4, TicketType::Complaint, TicketCategory::Cleaning, TicketPriority::Normal, 60 * 140, TicketStatus::Resolved, 'Classroom floors in the science wing not mopped after the weekend.', 'Science wing'],
            [0, TicketType::Request, TicketCategory::Cleaning, TicketPriority::Low, 60 * 200, TicketStatus::Closed, 'Window cleaning for the atrium before the visit next month.', 'Atrium'],
            [2, TicketType::Request, TicketCategory::Washroom, TicketPriority::Normal, 60 * 300, TicketStatus::Closed, 'Feminine hygiene units on the second floor are full.', 'Second floor'],
        ];
    }

    private function enquiries(): void
    {
        $rows = [
            [EnquiryTopic::Services, 'Sample Credit Union', 'Do you provide a once-off deep clean after an office refurbishment? About 400 m².', EnquiryStatus::New],
            [EnquiryTopic::Supplier, 'Sample Paper Products', 'We supply recycled washroom paper and would like to talk to your purchasing team.', EnquiryStatus::New],
            [EnquiryTopic::Careers, null, 'I applied for an evening cleaning job in Dublin last week. Is the role still open?', EnquiryStatus::Answered],
            [EnquiryTopic::Client, 'Sample Medical Practice', 'Can we change our cleaning hours from evenings to early mornings from next month?', EnquiryStatus::Answered],
            [EnquiryTopic::Other, null, 'A set of keys was found in the car park at Westlink Business Park. Are they yours?', EnquiryStatus::Closed],
        ];
        foreach ($rows as $i => [$topic, $org, $message, $status]) {
            $e = $this->at(now()->subDays($i * 3 + 1), fn () => Enquiry::create(['topic' => $topic, 'name' => $this->name(), 'organisation' => $org ? $org.' (sample)' : null, 'email' => 'enquiry'.($i + 1).'@example.ie', 'phone' => $this->phone(), 'message' => $message.' (sample)', 'status' => $status]));
            $e->log('Received from the website.');
        }
    }
}
