<?php

namespace App\Models;

use App\Contracts\Submission;
use App\Enums\Area;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\HasTimeline;
use Database\Factories\ServiceTicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A request or complaint logged by a client through their signed service-desk link. Two SLA
 * clocks run from the moment it is logged: first response and resolution (TicketPriority).
 *
 * @property int $id
 * @property string $reference
 * @property int $client_account_id
 * @property TicketType $type
 * @property TicketCategory $category
 * @property TicketPriority $priority
 * @property TicketStatus $status
 * @property string $reporter_name
 * @property string|null $reporter_email
 * @property string|null $reporter_phone
 * @property string|null $location
 * @property string $description
 * @property Carbon $response_due_at
 * @property Carbon $resolve_due_at
 * @property Carbon|null $responded_at
 * @property Carbon|null $resolved_at
 * @property int|null $assigned_to
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read ClientAccount $account
 * @property-read User|null $assignee
 * @property-read Collection<int, TicketPhoto> $photos
 */
class ServiceTicket extends Model implements Submission
{
    /** @use HasFactory<ServiceTicketFactory> */
    use HasFactory, HasReference, HasTimeline;

    /** A clock counts as "due soon" inside this many minutes of its deadline. */
    public const int DUE_SOON_MINUTES = 60;

    public const array PHOTO_TYPES = ['jpg', 'jpeg', 'png', 'heic', 'webp'];

    protected $fillable = ['type', 'category', 'priority', 'status', 'reporter_name', 'reporter_email', 'reporter_phone', 'location', 'description', 'response_due_at', 'resolve_due_at', 'responded_at', 'resolved_at', 'assigned_to', 'status_changed_at'];

    public static function referencePrefix(): string
    {
        return 'SR';
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'response_due_at' => 'datetime',
            'resolve_due_at' => 'datetime',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            $ticket->status ??= TicketStatus::New;
            $from = $ticket->created_at ?? now();
            $ticket->response_due_at ??= $from->copy()->addHours($ticket->priority->responseHours());
            $ticket->resolve_due_at ??= $from->copy()->addHours($ticket->priority->resolveHours());
        });
    }

    /** @return BelongsTo<ClientAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class, 'client_account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<TicketPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(TicketPhoto::class)->orderBy('id');
    }

    /**
     * Moves to $status with a conditional UPDATE, stamping the response and resolution clocks
     * the first time they stop. Returns false when someone else changed it first.
     */
    public function claimStatus(TicketStatus $status): bool
    {
        if ($this->status === $status) {
            return false;
        }
        $now = now();
        $changes = ['status' => $status->value, 'status_changed_at' => $now];
        if ($status !== TicketStatus::New && $this->responded_at === null) {
            $changes['responded_at'] = $now;
        }
        if (in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true) && $this->resolved_at === null) {
            $changes['resolved_at'] = $now;
        }
        if ($status->isOpen() && $this->resolved_at !== null) {
            $changes['resolved_at'] = null; // reopened: the resolution clock runs again
        }
        $claimed = static::query()->whereKey($this->getKey())->where('status', $this->getRawOriginal('status'))->update($changes) === 1;
        if ($claimed) {
            $this->forceFill(['status' => $status] + $changes)->syncOriginal();
        }

        return $claimed;
    }

    /**
     * The state of one SLA clock: met | breached | overdue | due-soon | on-track.
     * "breached" means it stopped late; "overdue" means it is still running past its deadline.
     */
    public function clock(string $which): string
    {
        [$due, $stopped] = $which === 'response' ? [$this->response_due_at, $this->responded_at] : [$this->resolve_due_at, $this->resolved_at];
        if ($stopped !== null) {
            return $stopped->lte($due) ? 'met' : 'breached';
        }
        if (now()->gt($due)) {
            return 'overdue';
        }

        return now()->diffInMinutes($due) <= self::DUE_SOON_MINUTES ? 'due-soon' : 'on-track';
    }

    /** The clock staff must act on next: response until it stops, then resolution. */
    public function activeClock(): string
    {
        return $this->responded_at === null ? 'response' : 'resolve';
    }

    public function activeDue(): Carbon
    {
        return $this->activeClock() === 'response' ? $this->response_due_at : $this->resolve_due_at;
    }

    /** "Respond in 35 min", "Resolve 2 h overdue", "Resolved" */
    public function slaLabel(): string
    {
        if (! $this->status->isOpen()) {
            return $this->clock('resolve') === 'breached' ? 'Resolved late' : 'Resolved in time';
        }
        $verb = $this->activeClock() === 'response' ? 'Respond' : 'Resolve';
        $mins = (int) now()->diffInMinutes($this->activeDue(), false);

        return $mins >= 0 ? $verb.' in '.duration_short($mins) : $verb.' '.duration_short($mins).' overdue';
    }

    public function slaTone(): string
    {
        if (! $this->status->isOpen()) {
            return $this->clock('resolve') === 'breached' ? 'attention' : 'good';
        }

        return match ($this->clock($this->activeClock())) {
            'overdue' => 'critical',
            'due-soon' => 'attention',
            default => 'neutral',
        };
    }

    /**
     * Open tickets whose running clock has passed, or passes within DUE_SOON_MINUTES.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAtRisk(Builder $query): void
    {
        $soon = now()->addMinutes(self::DUE_SOON_MINUTES);
        $query->whereIn('status', [TicketStatus::New->value, TicketStatus::Acknowledged->value, TicketStatus::InProgress->value])
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $r) => $r->whereNull('responded_at')->where('response_due_at', '<=', $soon))
                ->orWhere(fn (Builder $r) => $r->whereNotNull('responded_at')->where('resolve_due_at', '<=', $soon)));
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [TicketStatus::New->value, TicketStatus::Acknowledged->value, TicketStatus::InProgress->value]);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['priority'] ?? null, fn (Builder $q, $v) => $q->where('priority', $v))
            ->when($f['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->when($f['region'] ?? null, fn (Builder $q, $v) => $q->whereHas('account', fn (Builder $a) => $a->where('region', $v)))
            ->when($f['account'] ?? null, fn (Builder $q, $v) => $q->where('client_account_id', $v))
            ->when($f['risk'] ?? null, fn (Builder $q) => $q->atRisk())
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $q->where(fn (Builder $w) => $w->where('reference', 'like', $like)->orWhere('reporter_name', 'like', $like)
                    ->orWhere('description', 'like', $like)->orWhereHas('account', fn (Builder $a) => $a->where('organisation', 'like', $like)->orWhere('site_name', 'like', $like)));
            });
    }

    public function referenceNumber(): string
    {
        return $this->reference;
    }

    public function kindLabel(): string
    {
        return 'Service '.strtolower($this->type->label());
    }

    public function area(): Area
    {
        return Area::ServiceDesk;
    }

    public function senderName(): string
    {
        return $this->reporter_name;
    }

    public function senderEmail(): ?string
    {
        return $this->reporter_email;
    }

    public function summary(): array
    {
        return array_filter([
            'Site' => $this->account->displayName(),
            'Region' => $this->account->region->label(),
            'Type' => $this->type->label().' · '.$this->category->label(),
            'Priority' => $this->priority->label().' (respond by '.$this->response_due_at->format('D j M, H:i').')',
            'Where on site' => (string) $this->location,
            'Logged by' => $this->reporter_name.($this->reporter_phone ? ' · '.format_phone($this->reporter_phone) : ''),
            'Details' => mb_strimwidth($this->description, 0, 300, '…'),
        ]);
    }

    public function adminUrl(): string
    {
        return route('admin.tickets.show', $this);
    }

    public function nextSteps(): string
    {
        return 'Our operations team will respond by '.$this->response_due_at->format('l j F, H:i').'. You can follow progress on the same private link you used to log it.';
    }
}
