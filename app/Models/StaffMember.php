<?php

namespace App\Models;

use App\Enums\Region;
use App\Enums\RoleType;
use App\Enums\VettingStatus;
use App\Models\Concerns\HasTimeline;
use Database\Factories\StaffMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Someone on the books: HR's register of PSA licences, Garda vetting and right-to-work checks.
 * `app:remind-expiring` emails HR before a PSA licence lapses.
 *
 * @property int $id
 * @property string $employee_no
 * @property string $first_name
 * @property string $last_name
 * @property RoleType $role_type
 * @property Region $region
 * @property string|null $phone
 * @property string|null $email
 * @property Carbon|null $started_on
 * @property bool $active
 * @property string|null $psa_licence_number
 * @property Carbon|null $psa_licence_expires_on
 * @property int $psa_reminder_stage
 * @property Carbon|null $psa_reminded_at
 * @property VettingStatus $vetting_status
 * @property Carbon|null $vetting_cleared_on
 * @property Carbon|null $rtw_checked_on
 * @property int|null $job_application_id
 * @property bool $is_demo
 * @property Carbon $created_at
 * @property-read string $full_name
 * @property-read JobApplication|null $application
 */
class StaffMember extends Model
{
    /** @use HasFactory<StaffMemberFactory> */
    use HasFactory, HasTimeline;

    /** PSA licences expiring within this many days are flagged, and HR is emailed. */
    public const int EXPIRY_WARNING_DAYS = 60;

    public const int STAGE_NONE = 0;

    public const int STAGE_EXPIRING = 1;

    public const int STAGE_EXPIRED = 2;

    protected $fillable = [
        'employee_no', 'first_name', 'last_name', 'role_type', 'region', 'phone', 'email', 'started_on', 'active',
        'psa_licence_number', 'psa_licence_expires_on', 'vetting_status', 'vetting_cleared_on', 'rtw_checked_on', 'job_application_id', 'is_demo',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'role_type' => RoleType::class,
            'region' => Region::class,
            'vetting_status' => VettingStatus::class,
            'started_on' => 'date',
            'psa_licence_expires_on' => 'date',
            'psa_reminded_at' => 'datetime',
            'vetting_cleared_on' => 'date',
            'rtw_checked_on' => 'date',
            'active' => 'boolean',
            'is_demo' => 'boolean',
            'psa_reminder_stage' => 'integer',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $staff): void {
            $staff->vetting_status ??= VettingStatus::NotStarted;
        });
        // A renewed licence (new expiry date) starts the reminders again.
        static::saving(function (self $staff): void {
            if ($staff->isDirty('psa_licence_expires_on') && $staff->exists) {
                $staff->setAttribute('psa_reminder_stage', self::STAGE_NONE);
                $staff->setAttribute('psa_reminded_at', null);
            }
        });
    }

    /** @return BelongsTo<JobApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** valid | expiring | expired | missing | n/a */
    public function psaState(): string
    {
        if (! $this->role_type->asksPsa() && ! $this->psa_licence_number) {
            return 'n/a';
        }
        if (! $this->psa_licence_expires_on) {
            return 'missing';
        }
        if ($this->psa_licence_expires_on->lt(today())) {
            return 'expired';
        }

        return $this->psa_licence_expires_on->lte(today()->addDays(self::EXPIRY_WARNING_DAYS)) ? 'expiring' : 'valid';
    }

    public function daysLeft(): ?int
    {
        return $this->psa_licence_expires_on ? (int) today()->diffInDays($this->psa_licence_expires_on, false) : null;
    }

    /** The reminder this licence is due now: STAGE_EXPIRED, STAGE_EXPIRING or STAGE_NONE. */
    public function dueStage(): int
    {
        return match ($this->psaState()) {
            'expired' => self::STAGE_EXPIRED,
            'expiring' => self::STAGE_EXPIRING,
            default => self::STAGE_NONE,
        };
    }

    /**
     * Active staff whose PSA licence has expired or expires within EXPIRY_WARNING_DAYS.
     *
     * @param  Builder<self>  $query
     */
    public function scopeLicenceDue(Builder $query): void
    {
        $query->where('active', true)->whereNotNull('psa_licence_expires_on')
            ->whereDate('psa_licence_expires_on', '<=', today()->addDays(self::EXPIRY_WARNING_DAYS));
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['role'] ?? null, fn (Builder $q, $v) => $q->where('role_type', $v))
            ->when($f['region'] ?? null, fn (Builder $q, $v) => $q->where('region', $v))
            ->when($f['due'] ?? null, fn (Builder $q) => $q->licenceDue())
            ->when(($f['inactive'] ?? null) === null, fn (Builder $q) => $q->where('active', true))
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $q->where(fn (Builder $w) => $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                    ->orWhere('employee_no', 'like', $like)->orWhere('psa_licence_number', 'like', $like));
            });
    }
}
