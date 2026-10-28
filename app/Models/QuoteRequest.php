<?php

namespace App\Models;

use App\Contracts\Submission;
use App\Enums\Area;
use App\Enums\QuoteStatus;
use App\Enums\Region;
use App\Enums\Role;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\HasTimeline;
use Database\Factories\QuoteRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A quote or site-survey request from a prospective client. The site's county decides the
 * region, and the region's sales owner is assigned automatically.
 *
 * @property int $id
 * @property string $reference
 * @property QuoteStatus $status
 * @property Region $region
 * @property int|null $assigned_to
 * @property list<string> $services
 * @property string $sector
 * @property string $organisation
 * @property string $contact_name
 * @property string|null $contact_role
 * @property string $email
 * @property string $phone
 * @property string $site_name
 * @property string $site_address
 * @property string $town
 * @property string $county
 * @property string|null $eircode
 * @property int $site_count
 * @property int|null $floor_area_m2
 * @property string|null $cover
 * @property Carbon|null $start_on
 * @property Carbon $survey_on
 * @property string $survey_window
 * @property bool $is_tender
 * @property string|null $tender_reference
 * @property Carbon|null $tender_closes_on
 * @property string|null $message
 * @property Carbon|null $survey_at
 * @property string|null $quoted_value
 * @property Carbon|null $quoted_at
 * @property Carbon|null $consented_at
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read User|null $assignee
 * @property-read Collection<int, QuoteFile> $files
 */
class QuoteRequest extends Model implements Submission
{
    /** @use HasFactory<QuoteRequestFactory> */
    use HasFactory, HasReference, HasTimeline;

    /** Unbooked surveys requested within this many days lead the sales dashboard. */
    public const int SURVEY_SOON_DAYS = 7;

    /** The earliest survey date a visitor may ask for, in working days from today. */
    public const int SURVEY_LEAD_DAYS = 2;

    public const array FILE_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'dwg'];

    public const array WINDOWS = ['morning' => 'Morning (9 am – 12 noon)', 'afternoon' => 'Afternoon (12 noon – 5 pm)', 'any' => 'Any time that day'];

    protected $fillable = [
        'status', 'region', 'assigned_to', 'services', 'sector', 'organisation', 'contact_name', 'contact_role', 'email', 'phone',
        'site_name', 'site_address', 'town', 'county', 'eircode', 'site_count', 'floor_area_m2', 'cover', 'start_on', 'survey_on',
        'survey_window', 'is_tender', 'tender_reference', 'tender_closes_on', 'message', 'survey_at', 'quoted_value', 'quoted_at',
        'consented_at', 'status_changed_at', 'source_url',
    ];

    public static function referencePrefix(): string
    {
        return 'QR';
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'region' => Region::class,
            'services' => 'array',
            'start_on' => 'date',
            'survey_on' => 'date',
            'tender_closes_on' => 'date',
            'survey_at' => 'datetime',
            'quoted_value' => 'decimal:2',
            'quoted_at' => 'datetime',
            'consented_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'is_tender' => 'boolean',
            'site_count' => 'integer',
            'floor_area_m2' => 'integer',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $quote): void {
            $quote->status ??= QuoteStatus::New;
            $quote->region ??= Region::forCounty($quote->county);
            $quote->assigned_to ??= self::ownerFor($quote->region)?->id;
        });
        static::saving(function (self $quote): void {
            $quote->setAttribute('phone_digits', preg_replace('/\D+/', '', $quote->phone) ?: null);
        });
    }

    /** The active sales user who covers $region (the first by name), or null. */
    public static function ownerFor(Region $region): ?User
    {
        return User::query()->active()->where('role', Role::Sales)->where('region', $region)->orderBy('name')->first();
    }

    /** The first date a visitor may request a survey for: SURVEY_LEAD_DAYS working days ahead. */
    public static function earliestSurvey(): Carbon
    {
        return today()->addWeekdays(self::SURVEY_LEAD_DAYS);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<QuoteFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(QuoteFile::class)->orderBy('id');
    }

    /** Conditional UPDATE so a double submit or two people at once apply a status once. */
    public function claimStatus(QuoteStatus $status): bool
    {
        if ($this->status === $status) {
            return false;
        }
        $now = now();
        $changes = ['status' => $status->value, 'status_changed_at' => $now];
        if ($status === QuoteStatus::Quoted && $this->quoted_at === null) {
            $changes['quoted_at'] = $now;
        }
        $claimed = static::query()->whereKey($this->getKey())->where('status', $this->getRawOriginal('status'))->update($changes) === 1;
        if ($claimed) {
            $this->forceFill(['status' => $status] + $changes)->syncOriginal();
        }

        return $claimed;
    }

    /** Not booked, and the requested survey date is within SURVEY_SOON_DAYS (or past). */
    public function isUnbooked(): bool
    {
        return $this->status === QuoteStatus::New && $this->survey_on->lte(today()->addDays(self::SURVEY_SOON_DAYS));
    }

    /** "In 3 days", "Tomorrow", "2 days ago" for the requested survey date. */
    public function surveyLabel(): string
    {
        $days = (int) today()->diffInDays($this->survey_on, false);

        return match (true) {
            $days === 0 => 'Today',
            $days === 1 => 'Tomorrow',
            $days > 1 => 'In '.$days.' days',
            $days === -1 => 'Yesterday',
            default => abs($days).' days ago',
        };
    }

    public function surveyTone(): string
    {
        $days = (int) today()->diffInDays($this->survey_on, false);

        return $days < 0 ? 'critical' : ($days <= 2 ? 'attention' : 'neutral');
    }

    /** @return list<string> service labels */
    public function serviceLabels(): array
    {
        return array_map(fn (string $s) => (string) (allpro('services.'.$s.'.title') ?? $s), $this->services);
    }

    /** @param Builder<self> $query */
    public function scopeNeedsBooking(Builder $query): void
    {
        $query->where('status', QuoteStatus::New)->whereDate('survey_on', '<=', today()->addDays(self::SURVEY_SOON_DAYS));
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', array_map(fn (QuoteStatus $s) => $s->value, array_filter(QuoteStatus::cases(), fn (QuoteStatus $s) => $s->isOpen())));
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['region'] ?? null, fn (Builder $q, $v) => $q->where('region', $v))
            ->when($f['service'] ?? null, fn (Builder $q, $v) => $q->where('services', 'like', '%"'.str_replace(['%', '_', '"'], '', (string) $v).'"%'))
            ->when($f['owner'] ?? null, fn (Builder $q, $v) => $v === 'none' ? $q->whereNull('assigned_to') : $q->where('assigned_to', $v))
            ->when($f['booking'] ?? null, fn (Builder $q) => $q->needsBooking())
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $digits = preg_replace('/\D+/', '', (string) $term);
                $q->where(function (Builder $w) use ($like, $digits) {
                    $w->where('organisation', 'like', $like)->orWhere('site_name', 'like', $like)->orWhere('reference', 'like', $like)
                        ->orWhere('contact_name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('town', 'like', $like);
                    if (strlen((string) $digits) >= 3) {
                        $w->orWhere('phone_digits', 'like', '%'.$digits.'%');
                    }
                });
            });
    }

    public function referenceNumber(): string
    {
        return $this->reference;
    }

    public function kindLabel(): string
    {
        return 'Quote request';
    }

    public function area(): Area
    {
        return Area::Quotes;
    }

    public function senderName(): string
    {
        return $this->contact_name;
    }

    public function senderEmail(): ?string
    {
        return $this->email;
    }

    public function summary(): array
    {
        return array_filter([
            'Organisation' => $this->organisation,
            'Services' => implode(', ', $this->serviceLabels()),
            'Site' => $this->site_name.', '.$this->town.', Co. '.$this->county,
            'Region' => $this->region->label().($this->assignee ? ' · '.$this->assignee->name : ' · not assigned'),
            'Survey requested' => $this->survey_on->format('l j F Y').' ('.(self::WINDOWS[$this->survey_window] ?? $this->survey_window).')',
            'Public tender' => $this->is_tender ? trim(($this->tender_reference ?? '').' closes '.$this->tender_closes_on?->format('j M Y')) : '',
            'Contact' => $this->contact_name.' · '.format_phone($this->phone).' · '.$this->email,
        ]);
    }

    public function adminUrl(): string
    {
        return route('admin.quotes.show', $this);
    }

    public function nextSteps(): string
    {
        return 'Our '.$this->region->label().' team will phone you to confirm a time for the site survey on or near the date you asked for. After the survey we send a written quote.';
    }
}
