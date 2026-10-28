<?php

namespace App\Models;

use App\Contracts\Submission;
use App\Enums\ApplicationStatus;
use App\Enums\Area;
use App\Enums\EmploymentType;
use App\Enums\Experience;
use App\Enums\Region;
use App\Enums\RightToWork;
use App\Enums\RoleType;
use App\Enums\VettingStatus;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\HasTimeline;
use App\Support\Availability;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * An application for a front-line role. The applicant gives their PSA licence (security roles),
 * right-to-work declaration and availability grid; HR records the checks and Garda vetting.
 *
 * @property int $id
 * @property string $reference
 * @property int|null $job_posting_id
 * @property RoleType $role_type
 * @property Region $region
 * @property ApplicationStatus $status
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string|null $email
 * @property string|null $county
 * @property Experience $experience
 * @property RightToWork $right_to_work
 * @property EmploymentType|null $employment_type
 * @property list<string>|null $availability
 * @property bool|null $drives
 * @property Carbon|null $available_from
 * @property string|null $psa_licence_number
 * @property Carbon|null $psa_licence_expires_on
 * @property Carbon|null $psa_checked_at
 * @property int|null $psa_checked_by
 * @property VettingStatus $vetting_status
 * @property Carbon|null $vetting_updated_at
 * @property Carbon|null $rtw_checked_at
 * @property int|null $rtw_checked_by
 * @property string|null $message
 * @property string|null $cv_path
 * @property string|null $cv_name
 * @property string|null $cv_mime
 * @property int|null $cv_size
 * @property Carbon|null $consented_at
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read string $full_name
 * @property-read JobPosting|null $jobPosting
 * @property-read StaffMember|null $staffMember
 * @property-read User|null $psaChecker
 * @property-read User|null $rtwChecker
 */
class JobApplication extends Model implements Submission
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory, HasReference, HasTimeline;

    public const array CV_TYPES = ['pdf', 'doc', 'docx'];

    /** New applications older than this many days count as waiting on the HR dashboard. */
    public const int REVIEW_DAYS = 2;

    protected $fillable = [
        'job_posting_id', 'role_type', 'region', 'status', 'first_name', 'last_name', 'phone', 'email', 'county', 'experience',
        'right_to_work', 'employment_type', 'availability', 'drives', 'available_from', 'psa_licence_number', 'psa_licence_expires_on',
        'message', 'cv_path', 'cv_name', 'cv_mime', 'cv_size', 'consented_at', 'status_changed_at',
    ];

    public static function referencePrefix(): string
    {
        return 'JA';
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'role_type' => RoleType::class,
            'region' => Region::class,
            'status' => ApplicationStatus::class,
            'experience' => Experience::class,
            'right_to_work' => RightToWork::class,
            'employment_type' => EmploymentType::class,
            'vetting_status' => VettingStatus::class,
            'availability' => 'array',
            'drives' => 'boolean',
            'available_from' => 'date',
            'psa_licence_expires_on' => 'date',
            'psa_checked_at' => 'datetime',
            'vetting_updated_at' => 'datetime',
            'rtw_checked_at' => 'datetime',
            'consented_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            $application->status ??= ApplicationStatus::New;
            $application->vetting_status ??= VettingStatus::NotStarted;
        });
        static::saving(function (self $application): void {
            $application->setAttribute('phone_digits', preg_replace('/\D+/', '', $application->phone) ?: null);
        });
    }

    /** @return BelongsTo<JobPosting, $this> */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /** @return HasOne<StaffMember, $this> */
    public function staffMember(): HasOne
    {
        return $this->hasOne(StaffMember::class);
    }

    /** @return BelongsTo<User, $this> */
    public function psaChecker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'psa_checked_by');
    }

    /** @return BelongsTo<User, $this> */
    public function rtwChecker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rtw_checked_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Moves to $status with a conditional UPDATE (only if nobody changed it since this record
     * was loaded), so a double click or two HR officers at once apply it once.
     */
    public function claimStatus(ApplicationStatus $status): bool
    {
        if ($this->status === $status) {
            return false;
        }
        $now = now();
        $claimed = static::query()->whereKey($this->getKey())->where('status', $this->getRawOriginal('status'))
            ->update(['status' => $status->value, 'status_changed_at' => $now]) === 1;
        if ($claimed) {
            $this->forceFill(['status' => $status, 'status_changed_at' => $now])->syncOriginal();
        }

        return $claimed;
    }

    public function isWaiting(): bool
    {
        return $this->status === ApplicationStatus::New && $this->created_at->lt(now()->subDays(self::REVIEW_DAYS));
    }

    /** valid | expiring | expired | missing | n/a, for the PSA licence the applicant gave. */
    public function psaState(): string
    {
        if (! $this->role_type->asksPsa()) {
            return 'n/a';
        }
        if (! $this->psa_licence_number || ! $this->psa_licence_expires_on) {
            return $this->role_type->needsPsa() ? 'missing' : 'n/a';
        }
        if ($this->psa_licence_expires_on->lt(today())) {
            return 'expired';
        }

        return $this->psa_licence_expires_on->lte(today()->addDays(StaffMember::EXPIRY_WARNING_DAYS)) ? 'expiring' : 'valid';
    }

    /**
     * What still stands between an offer and a start date. Hiring is blocked until this is empty.
     *
     * @return list<string>
     */
    public function outstandingChecks(): array
    {
        $out = [];
        if ($this->rtw_checked_at === null) {
            $out[] = 'Right to work not checked';
        }
        if ($this->vetting_status !== VettingStatus::Cleared) {
            $out[] = 'Garda vetting not cleared';
        }
        if ($this->role_type->needsPsa() && $this->psa_checked_at === null) {
            $out[] = 'PSA licence not checked on the register';
        }
        if ($this->role_type->needsPsa() && in_array($this->psaState(), ['expired', 'missing'], true)) {
            $out[] = 'PSA licence expired or missing';
        }

        return $out;
    }

    /** @param Builder<self> $query */
    public function scopeWaiting(Builder $query): void
    {
        $query->where('status', ApplicationStatus::New)->where('created_at', '<', now()->subDays(self::REVIEW_DAYS));
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['role'] ?? null, fn (Builder $q, $v) => $q->where('role_type', $v))
            ->when($f['region'] ?? null, fn (Builder $q, $v) => $q->where('region', $v))
            ->when($f['job'] ?? null, fn (Builder $q, $v) => $q->where('job_posting_id', $v))
            ->when($f['shift'] ?? null, fn (Builder $q, $v) => $q->where('availability', 'like', '%-'.str_replace(['%', '_', '"'], '', (string) $v).'"%'))
            ->when($f['waiting'] ?? null, fn (Builder $q) => $q->waiting())
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $digits = preg_replace('/\D+/', '', (string) $term);
                $q->where(function (Builder $w) use ($like, $digits) {
                    $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                        ->orWhere('reference', 'like', $like)->orWhere('email', 'like', $like)->orWhere('psa_licence_number', 'like', $like);
                    if (strlen((string) $digits) >= 3) {
                        $w->orWhere('phone_digits', 'like', '%'.$digits.'%');
                    }
                });
            });
    }

    public function position(): string
    {
        return $this->jobPosting->title ?? $this->role_type->label().', '.$this->region->label().' (general application)';
    }

    public function availabilitySummary(): string
    {
        return Availability::summary($this->availability);
    }

    public function referenceNumber(): string
    {
        return $this->reference;
    }

    public function kindLabel(): string
    {
        return 'Job application';
    }

    public function area(): Area
    {
        return Area::Careers;
    }

    public function senderName(): string
    {
        return $this->full_name;
    }

    public function senderEmail(): ?string
    {
        return $this->email;
    }

    public function summary(): array
    {
        return array_filter([
            'Applicant' => $this->full_name,
            'Position' => $this->position(),
            'Region' => $this->region->label(),
            'Experience' => $this->experience->label(),
            'Availability' => $this->availabilitySummary(),
            'PSA licence' => $this->role_type->asksPsa() ? ($this->psa_licence_number ? $this->psa_licence_number.', expires '.$this->psa_licence_expires_on?->format('j M Y') : 'Not given') : '',
            'Phone' => format_phone($this->phone),
            'CV attached' => $this->cv_path ? 'Yes' : 'No',
        ]);
    }

    public function adminUrl(): string
    {
        return route('admin.applications.show', $this);
    }

    public function nextSteps(): string
    {
        return 'Our HR team reads every application. If you suit a site near you, we will phone you on the number you gave us. If we offer you a role, we will send the Garda vetting invitation by email from the National Vetting Bureau; please do not send vetting documents to us any other way.';
    }
}
