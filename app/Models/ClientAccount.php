<?php

namespace App\Models;

use App\Enums\Region;
use App\Models\Concerns\HasTimeline;
use Database\Factories\ClientAccountFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * A client site on contract. The client's contact gets an expiring signed link to the service
 * desk for this site; the link also carries the account's access key, so "revoke" (a new key)
 * kills every link sent before.
 *
 * @property int $id
 * @property string $public_id
 * @property string $access_key
 * @property string $organisation
 * @property string $site_name
 * @property Region $region
 * @property string|null $address
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property list<string>|null $services
 * @property Carbon|null $link_expires_at
 * @property Carbon|null $last_opened_at
 * @property int|null $created_by
 * @property bool $is_demo
 * @property Carbon $created_at
 * @property-read Collection<int, ServiceTicket> $tickets
 * @property-read User|null $creator
 */
class ClientAccount extends Model
{
    /** @use HasFactory<ClientAccountFactory> */
    use HasFactory, HasTimeline;

    public const array LINK_DAYS = [30, 90, 365];

    protected $fillable = ['organisation', 'site_name', 'region', 'address', 'contact_name', 'contact_email', 'contact_phone', 'services', 'link_expires_at', 'last_opened_at', 'created_by', 'is_demo'];

    #[\Override]
    protected function casts(): array
    {
        return ['region' => Region::class, 'services' => 'array', 'link_expires_at' => 'datetime', 'last_opened_at' => 'datetime', 'is_demo' => 'boolean'];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $account): void {
            $account->public_id = strtolower((string) Str::ulid());
            $account->access_key = Str::random(40);
        });
    }

    /** @return HasMany<ServiceTicket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class)->orderByDesc('id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayName(): string
    {
        return $this->organisation.' · '.$this->site_name;
    }

    /** A new signed link valid for $days, and the expiry stored for display. */
    public function shareLink(int $days): string
    {
        $this->link_expires_at = now()->addDays($days);
        $this->save();

        return (string) $this->signedUrl();
    }

    /** The current link (same expiry), or null when none is live. */
    public function signedUrl(): ?string
    {
        if (! $this->linkIsLive()) {
            return null;
        }

        return URL::temporarySignedRoute('portal.show', $this->link_expires_at, ['account' => $this->public_id, 'key' => $this->access_key]);
    }

    public function linkIsLive(): bool
    {
        return $this->link_expires_at !== null && $this->link_expires_at->isFuture();
    }

    /** Revoke every link already sent: a new key, no expiry. */
    public function revoke(): void
    {
        $this->access_key = Str::random(40);
        $this->link_expires_at = null;
        $this->save();
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
